<?php

namespace App\Modules\Incident\Events;

use App\Modules\Incident\Models\Incident;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentReportedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Incident $incident) {}
}
