<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get list of notifications for authenticated user
     * 
     * GET /api/notifications
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 15);

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data'   => $notifications,
            'unread_count' => Notification::where('user_id', $user->id)->unread()->count()
        ]);
    }

    /**
     * Get unread notification count for authenticated user
     *
     * GET /api/notifications/unread-count
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        $unreadCount = Notification::where('user_id', $user->id)->unread()->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Mark a specific notification as read
     * 
     * PATCH /api/notifications/{id}/read
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsRead($id, Request $request)
    {
        $user = $request->user();
        
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'Notifikasi ditandai sebagai sudah dibaca.',
        ]);
    }

    /**
     * Mark all unread notifications as read for authenticated user
     * 
     * POST /api/notifications/read-all
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        Notification::where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Semua notifikasi ditandai sebagai sudah dibaca.',
        ]);
    }
}
