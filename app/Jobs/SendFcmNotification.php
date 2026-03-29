<?php

namespace App\Jobs;

use App\Models\UserDeviceToken;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;

class SendFcmNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param array|string $tokens FCM device tokens (array or single token)
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Custom data payload
     */
    public function __construct(
        protected array|string $tokens,
        protected string $title,
        protected string $body,
        protected array $data = []
    ) {
        // Convert single token to array
        if (is_string($this->tokens)) {
            $this->tokens = [$this->tokens];
        }
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService): void
    {
        // Filter out empty/placeholder/obviously-invalid tokens.
        $filteredTokens = [];
        foreach ($this->tokens as $token) {
            if (!is_string($token)) {
                continue;
            }
            $token = trim($token);
            if ($token === '' || $token === 'YOUR_FCM_TOKEN_HERE' || strlen($token) < 50) {
                continue;
            }
            $filteredTokens[] = $token;
        }
        $this->tokens = array_values(array_unique($filteredTokens));

        if (empty($this->tokens)) {
            Log::warning('SendFcmNotification: No tokens provided');
            return;
        }

        try {
            $messaging = $firebaseService->getMessaging();
            
            // Split tokens into batches of 500 (FCM limit)
            $batches = array_chunk($this->tokens, 500);
            
            $totalSuccess = 0;
            $totalFailure = 0;
            $invalidTokens = [];

            foreach ($batches as $batchIndex => $batch) {
                try {
                    // Create notification payload
                    $notification = Notification::create($this->title, $this->body);
                    
                    // Create message with notification and data
                    $message = CloudMessage::new()
                        ->withNotification($notification)
                        ->withData($this->data)
                        ->withAndroidConfig([
                            'priority' => 'high',
                            'notification' => [
                                'sound' => 'default',
                                'channel_id' => ($this->data['source'] ?? '') === 'sc' ? 'sc_notifications' : 'pum_notifications',
                            ],
                        ])
                        ->withApnsConfig([
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                ],
                            ],
                        ]);

                    // Send multicast message
                    $report = $messaging->sendMulticast($message, $batch);

                    $successCount = $report->successes()->count();
                    $failureCount = $report->failures()->count();
                    
                    $totalSuccess += $successCount;
                    $totalFailure += $failureCount;

                    // Extract invalid tokens from failures
                    foreach ($report->failures()->getItems() as $failure) {
                        $error = $failure->error();
                        $token = $failure->target()->value();
                        
                        // Check if error indicates invalid token
                        if ($this->isInvalidTokenError($error)) {
                            $invalidTokens[] = $token;
                        }
                    }

                    Log::info('SendFcmNotification: Batch sent', [
                        'batch' => $batchIndex + 1,
                        'total_batches' => count($batches),
                        'batch_size' => count($batch),
                        'success' => $successCount,
                        'failure' => $failureCount,
                    ]);

                } catch (MessagingException $e) {
                    Log::error('SendFcmNotification: Messaging error in batch', [
                        'batch' => $batchIndex + 1,
                        'error' => $e->getMessage(),
                    ]);
                    $totalFailure += count($batch);
                }
            }

            // Cleanup invalid tokens
            if (!empty($invalidTokens)) {
                $this->cleanupInvalidTokens($invalidTokens);
            }

            Log::info('SendFcmNotification: Job completed', [
                'total_tokens' => count($this->tokens),
                'total_success' => $totalSuccess,
                'total_failure' => $totalFailure,
                'invalid_tokens_removed' => count($invalidTokens),
                'title' => $this->title,
            ]);

        } catch (FirebaseException $e) {
            Log::error('SendFcmNotification: Firebase error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('SendFcmNotification: Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if error indicates an invalid token
     *
     * @param \Kreait\Firebase\Messaging\MessageTarget $error
     * @return bool
     */
    private function isInvalidTokenError($error): bool
    {
        $errorMessage = $error->getMessage();
        
        // Common invalid token error messages
        $invalidTokenPatterns = [
            'registration-token-not-registered',
            'invalid-registration-token',
            'invalid-argument',
            'registration token is invalid',
            'not a valid fcm registration token',
            'not a valid fcm registration',
            'requested entity was not found',
        ];

        foreach ($invalidTokenPatterns as $pattern) {
            if (stripos($errorMessage, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove invalid tokens from database
     *
     * @param array $tokens
     * @return void
     */
    private function cleanupInvalidTokens(array $tokens): void
    {
        try {
            $deleted = UserDeviceToken::whereIn('device_token', $tokens)->delete();
            
            Log::info('SendFcmNotification: Cleaned up invalid tokens', [
                'tokens_removed' => $deleted,
            ]);
        } catch (\Exception $e) {
            Log::error('SendFcmNotification: Failed to cleanup invalid tokens', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
