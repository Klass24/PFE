<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Mission\Events\MissionStartedEvent;
use App\Modules\Notification\Services\NotificationService;

class NotifyDriverOnMissionAssignedListener
{
    public function __construct(private NotificationService $service) {}

    public function handle(MissionStartedEvent $event): void
    {
        $mission = $event->mission;

        $driverUserId = $mission->driver?->user_id;

        if (!$driverUserId) {
            return;
        }

        $message = "Votre mission de {$mission->origin} → {$mission->destination} a démarré.";

        $this->service->create(
            $mission->company_id,
            'mission_started',
            $message,
            $driverUserId
        );
    }
}
