<?php

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Models\Notification;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Crée une notification dashboard (toujours) et optionnellement email/whatsapp.
     */
    public function create(
        int $companyId,
        string $type,
        string $message,
        ?int $userId = null,
        string $channel = 'dashboard'
    ): Notification {
        $notification = Notification::create([
            'company_id' => $companyId,
            'user_id'    => $userId,
            'type'       => $type,
            'message'    => $message,
            'channel'    => $channel,
            'sent_at'    => now(),
        ]);

        Log::info('Notification created', [
            'type'       => $type,
            'company_id' => $companyId,
            'user_id'    => $userId,
        ]);

        return $notification;
    }

    /**
     * Notifie tous les gestionnaires/admins d'une entreprise.
     */
    public function notifyManagers(int $companyId, string $type, string $message): void
    {
        $managers = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
            ->get();

        foreach ($managers as $manager) {
            $this->create($companyId, $type, $message, $manager->id);
        }
    }

    /**
     * Liste paginée des notifications de l'utilisateur connecté.
     */
    public function paginate(bool $unreadOnly = false): LengthAwarePaginator
    {
        $query = Notification::where('user_id', auth()->id())
            ->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        return $query->paginate(20);
    }

    /**
     * Marquer comme lu.
     */
    public function markAsRead(Notification $notification): Notification
    {
        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        return $notification;
    }

    /**
     * Marquer toutes les notifications non lues de l'utilisateur comme lues.
     */
    public function markAllAsRead(int $userId, int $companyId): int
    {
        return Notification::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function countUnread(int $userId): int
    {
        return Notification::where('user_id', $userId)->where('is_read', false)->count();
    }
}
