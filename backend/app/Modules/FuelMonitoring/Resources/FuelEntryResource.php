<?php

namespace App\Modules\FuelMonitoring\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FuelEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'             => $this->uuid,
            'liters'           => $this->liters,
            'cost_per_liter'   => $this->cost_per_liter,
            'total_cost'       => $this->total_cost,
            'odometer_reading' => $this->odometer_reading,
            'station_name'     => $this->station_name,
            'latitude'         => $this->latitude,
            'longitude'        => $this->longitude,
            'recorded_at'      => $this->recorded_at?->toIso8601String(),
            'vehicle'          => $this->whenLoaded('vehicle', fn() => [
                'uuid'         => $this->vehicle->uuid,
                'plate_number' => $this->vehicle->plate_number,
            ]),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
