<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDeviceToken;
use App\Services\FirebaseService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class FcmTokenController extends Controller
{
    public function __construct(
        private FirebaseService $firebaseService,
        private NotificationService $notificationService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Register FCM device token for the authenticated user
     * 
     * POST /api/fcm-token
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => 'required|string|max:500',
            'device_type' => ['nullable', Rule::in(['android', 'ios', 'web'])],
        ]);

        try {
            // Use updateOrCreate to avoid duplicate tokens
            $deviceToken = UserDeviceToken::updateOrCreate(
                [
                    'device_token' => $validated['device_token'],
                ],
                [
                    'user_id' => auth()->id(),
                    'device_type' => $validated['device_type'] ?? null,
                ]
            );

            Log::info('FCM token registered', [
                'user_id' => auth()->id(),
                'device_type' => $validated['device_type'] ?? 'unknown',
                'token_id' => $deviceToken->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'FCM Token berhasil didaftarkan',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to register FCM token', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mendaftarkan FCM token',
            ], 500);
        }
    }

    /**
     * Remove FCM device token (logout)
     * 
     * DELETE /api/fcm-token
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => 'required|string',
        ]);

        try {
            $deleted = UserDeviceToken::where('device_token', $validated['device_token'])
                ->where('user_id', auth()->id())
                ->delete();

            if ($deleted) {
                Log::info('FCM token removed', [
                    'user_id' => auth()->id(),
                    'device_token' => substr($validated['device_token'], 0, 20) . '...',
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'FCM Token berhasil dihapus',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'FCM Token tidak ditemukan atau sudah dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove FCM token', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus FCM token',
            ], 500);
        }
    }

    /**
     * Test Firebase connection
     * 
     * GET /api/firebase/ping
     * 
     * @return JsonResponse
     */
    public function ping(): JsonResponse
    {
        try {
            $result = $this->firebaseService->ping();

            return response()->json([
                'status' => 'success',
                'message' => 'Firebase connection successful',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Firebase ping failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Firebase connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send test notification to authenticated user's devices
     * 
     * POST /api/test-notification
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function testNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:1000',
        ]);

        try {
            $user = auth()->user();
            
            // Get all device tokens for the authenticated user
            $tokens = UserDeviceToken::where('user_id', $user->id)->get();

            if ($tokens->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada device token terdaftar untuk user ini',
                ], 404);
            }

            // Prepare notification content
            $title = $validated['title'] ?? 'Test Notifikasi';
            $body = $validated['body'] ?? 'Ini adalah test notification dari Supply Chain API';

            // Prepare custom data payload
            $data = [
                'type' => 'test_notification',
                'sent_at' => now()->toIso8601String(),
                'user_id' => (string)$user->id,
            ];

            // Send notification using NotificationService
            $this->notificationService->notifyUser($user, $title, $body, $data);

            Log::info('Test notification sent', [
                'user_id' => $user->id,
                'token_count' => $tokens->count(),
                'title' => $title,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Test notification berhasil dikirim',
                'data' => [
                    'device_count' => $tokens->count(),
                    'title' => $title,
                    'body' => $body,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim test notification: ' . $e->getMessage(),
            ], 500);
        }
    }
}
