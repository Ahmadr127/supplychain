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

    protected array $tokens;
    protected string $title;
    protected string $body;
    protected array $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array|string $tokens, string $title, string $body, array $data = [])
    {
        $this->tokens = is_array($tokens) ? $tokens : [$tokens];
        $this->title  = $title;
        $this->body   = $body;
        $this->data   = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(FirebaseService $firebaseService): void
    {
        $messaging = $firebaseService->getMessaging();

        // Runtime evidence: token statistics (avoid logging raw tokens).
        $tokensSnapshot        = $this->tokens;
        $tokenCount            = count($tokensSnapshot);
        $placeholderExactCount = 0;
        $minLen                = null;
        $maxLen                = null;
        $tokenHashSamples      = [];
        foreach ($tokensSnapshot as $t) {
            if (! is_string($t)) {
                continue;
            }
            if ($t === 'YOUR_FCM_TOKEN_HERE') {
                $placeholderExactCount++;
            }
            $len    = strlen($t);
            $minLen = $minLen === null ? $len : min($minLen, $len);
            $maxLen = $maxLen === null ? $len : max($maxLen, $len);
            if (count($tokenHashSamples) < 3 && $t !== '') {
                $tokenHashSamples[] = substr(hash('sha256', $t), 0, 12);
            }
        }

        Log::info('[FCM DEBUG] Job about to send multicast (sc)', [
            'token_count'            => $tokenCount,
            'placeholder_exact_count' => $placeholderExactCount,
            'token_length'           => ['min' => $minLen, 'max' => $maxLen],
            'title'                  => $this->title,
            'device_tokens'          => $this->tokens,
        ]);

        $notification = Notification::create($this->title, $this->body);

        // Filter out empty/placeholder/obviously-invalid tokens.
        $dropped = [
            'empty'            => 0,
            'non_string'       => 0,
            'placeholder_exact' => 0,
            'too_short'        => 0,
        ];
        $tokens = [];
        foreach ($this->tokens as $t) {
            if (! is_string($t)) {
                $dropped['non_string']++;
                continue;
            }
            $t = trim($t);
            if ($t === '') {
                $dropped['empty']++;
                continue;
            }
            if ($t === 'YOUR_FCM_TOKEN_HERE') {
                $dropped['placeholder_exact']++;
                continue;
            }
            // Typical FCM registration tokens are long; short tokens are always invalid.
            if (strlen($t) < 50) {
                $dropped['too_short']++;
                continue;
            }
            $tokens[] = $t;
        }
        $tokens = array_values(array_unique($tokens));
        Log::info('[FCM DEBUG] Token filtering summary (sc)', [
            'kept'    => count($tokens),
            'dropped' => $dropped,
        ]);

        if (empty($tokens)) {
            return;
        }

        // Send in chunks of 500 (FCM limit for multicast)
        $chunks              = array_chunk($tokens, 500);
        $firstFailureLogged  = false;

        foreach ($chunks as $chunk) {
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($this->data)
                ->withAndroidConfig([
                    'priority'     => 'high',
                    'notification' => [
                        'sound'      => 'default',
                        'channel_id' => 'sc_notifications',
                    ],
                ])
                ->withApnsConfig([
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ]);

            try {
                Log::info('Sending FCM Notification to ' . count($chunk) . " devices. Title: {$this->title}");

                $report = $messaging->sendMulticast($message, $chunk);

                Log::info('FCM Send Report: Success: ' . $report->successes()->count() . ', Fail: ' . $report->failures()->count());

                // Cleanup invalid tokens
                if ($report->hasFailures()) {
                    foreach ($report->failures()->getItems() as $failure) {
                        $reason      = $failure->error()->getMessage();
                        $targetToken = $failure->target()->value();

                        // Log only the first failure to keep output small.
                        if ($firstFailureLogged === false) {
                            $matchesCleanup =
                                (str_contains($reason, 'invalid-registration-token') ||
                                    str_contains($reason, 'registration-token-not-registered'));
                            Log::info('[FCM DEBUG] First failure reason + cleanup match (sc)', [
                                'fcm_reason'                           => $reason,
                                'cleanup_condition_matches_patterns'   => $matchesCleanup,
                                'target_sha256_prefix'                 => substr(hash('sha256', $targetToken), 0, 12),
                            ]);
                            $firstFailureLogged = true;
                        }

                        // If token is invalid or not registered, delete it
                        $reasonLower = strtolower($reason);
                        if (str_contains($reasonLower, 'invalid-registration-token') ||
                            str_contains($reasonLower, 'registration-token-not-registered') ||
                            str_contains($reasonLower, 'not a valid fcm registration token') ||
                            str_contains($reasonLower, 'registration token is not a valid fcm registration token') ||
                            str_contains($reasonLower, 'requested entity was not found')) {
                            $deleted   = UserDeviceToken::where('device_token', $targetToken)->delete();
                            $remaining = UserDeviceToken::where('device_token', $targetToken)->count();
                            Log::warning("Removing invalid FCM Token (sc). Reason: {$reason}", [
                                'deleted_rows'              => $deleted,
                                'remaining_rows_for_token'  => $remaining,
                                'token_sha256_prefix'       => substr(hash('sha256', $targetToken), 0, 12),
                            ]);
                        } else {
                            Log::error("FCM Delivery Failed for token (masked, sc). Reason: {$reason}");
                        }
                    }
                }
            } catch (MessagingException $e) {
                Log::error('SendFcmNotification (sc): Messaging error', ['error' => $e->getMessage()]);
            } catch (FirebaseException $e) {
                Log::error('SendFcmNotification (sc): Firebase error', ['error' => $e->getMessage()]);
                throw $e;
            } catch (\Exception $e) {
                Log::error('Failed to send FCM Multicast (sc): ' . $e->getMessage());
            }
        }
    }
}
