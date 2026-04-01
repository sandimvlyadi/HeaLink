<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List notifications for the authenticated user.
     *
     * Returns a paginated list of in-app notifications ordered by most
     * recent. The `meta` object includes `unread_count` as a convenience
     * for badge display.
     *
     * @queryParam page int Page number. Example: 1
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->appNotifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $request->user()->appNotifications()->where('is_read', false)->count(),
            ],
        ]);
    }

    /**
     * Mark a single notification as read.
     *
     * Marks the notification identified by its UUID as read. Returns 403
     * if the notification does not belong to the authenticated user.
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'meta' => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca',
            'data' => new NotificationResource($notification),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    /**
     * Mark all notifications as read.
     *
     * Bulk-updates all unread notifications for the authenticated user to
     * `is_read = true`. Returns 200 with no data payload.
     */
    public function readAll(Request $request): JsonResponse
    {
        $request->user()
            ->appNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sudah dibaca',
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }
}
