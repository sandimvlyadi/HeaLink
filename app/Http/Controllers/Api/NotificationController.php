<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->appNotifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => NotificationResource::collection($notifications),
            'meta'    => [
                'timestamp'    => now()->toIso8601String(),
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
                'unread_count' => $request->user()->appNotifications()->where('is_read', false)->count(),
            ],
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'meta'    => ['timestamp' => now()->toIso8601String()],
            ], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca',
            'data'    => new NotificationResource($notification),
            'meta'    => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()
            ->appNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sudah dibaca',
            'meta'    => ['timestamp' => now()->toIso8601String()],
        ]);
    }
}
