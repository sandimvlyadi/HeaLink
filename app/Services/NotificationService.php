<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Create and persist a notification for a specific user.
     *
     * @param  array<string, mixed>  $actionData
     */
    public function notify(
        User $user,
        string $title,
        string $message,
        string $type = 'info',
        array $actionData = [],
    ): Notification {
        return Notification::create([
            'user_id'     => $user->id,
            'title'       => $title,
            'message'     => $message,
            'type'        => $type,
            'is_read'     => false,
            'action_data' => empty($actionData) ? null : $actionData,
        ]);
    }

    /**
     * Notify all active medics about a patient event.
     *
     * @param  array<string, mixed>  $actionData
     */
    public function notifyMedics(
        User $patient,
        string $title,
        string $message,
        string $type = 'info',
        array $actionData = [],
    ): void {
        User::where('role', 'medic')
            ->where('is_active', true)
            ->each(fn (User $medic) => $this->notify($medic, $title, $message, $type, $actionData));
    }

    /**
     * Notify all active admins.
     *
     * @param  array<string, mixed>  $actionData
     */
    public function notifyAdmins(
        string $title,
        string $message,
        string $type = 'info',
        array $actionData = [],
    ): void {
        User::where('role', 'admin')
            ->where('is_active', true)
            ->each(fn (User $admin) => $this->notify($admin, $title, $message, $type, $actionData));
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->update(['is_read' => true]);
    }

    /**
     * Mark all notifications for a user as read.
     */
    public function markAllAsRead(User $user): int
    {
        return $user->appNotifications()->where('is_read', false)->update(['is_read' => true]);
    }
}

