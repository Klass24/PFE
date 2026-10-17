<?php

namespace App\Modules\Mission\Events;

use App\Modules\Mission\Models\Mission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MissionCompletedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Mission $mission) {}
}
