<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Incident\Events\IncidentReportedEvent;
use App\Modules\Notification\Services\NotificationService;

class NotifyManagerOnIncidentListener
{
    public function __construct(private NotificationService $service) {}

    public function handle(IncidentReportedEvent $event): void
    {
        $incident = $event->incident;

        $typeLabel = match ($incident->type) {
            'accident'         => 'Accident',
            'breakdown'        => 'Panne',
            'flat_tire'        => 'Crevaison',
            'police_check'     => 'Contrôle de police',
            'mechanical_issue' => 'Problème mécanique',
            default            => 'Incident',
        };

        $message = "{$typeLabel} signalé sur la mission #{$incident->mission_id} par le chauffeur #{$incident->driver_id}.";

        $this->service->notifyManagers($incident->company_id, 'incident_reported', $message);
    }
}
