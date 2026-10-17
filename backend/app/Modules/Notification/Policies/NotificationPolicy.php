<?php

namespace App\Modules\Notification\Policies;

use App\Modules\Notification\Models\Notification;
use App\Modules\User\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Chaque user voit ses notifs (filtré par user_id)
    }

    public function view(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }

    public function markAsRead(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }
}
