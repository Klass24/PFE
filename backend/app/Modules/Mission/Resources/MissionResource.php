<?php

namespace App\Modules\Mission\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'origin'      => $this->origin,
            'destination' => $this->destination,
            'start_date'  => $this->start_date?->toIso8601String(),
            'end_date'    => $this->end_date?->toIso8601String(),
            'status'      => $this->status,
            'notes'       => $this->notes,
            'vehicle'     => $this->whenLoaded('vehicle', fn() => [
                'uuid'         => $this->vehicle->uuid,
                'plate_number' => $this->vehicle->plate_number,
                'brand'        => $this->vehicle->brand,
                'model'        => $this->vehicle->model,
            ]),
            'driver'      => $this->whenLoaded('driver', fn() => [
                'uuid'           => $this->driver->uuid,
                'license_number' => $this->driver->license_number,
                'name'           => $this->driver->user?->name,
            ]),
            'expenses_count'  => $this->whenLoaded('expenses', fn() => $this->expenses->count()),
            'incidents_count' => $this->whenLoaded('incidents', fn() => $this->incidents->count()),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
