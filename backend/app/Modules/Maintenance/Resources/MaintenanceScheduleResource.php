<?php

namespace App\Modules\Maintenance\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'maintenance_type'  => $this->maintenance_type,
            'frequency_km'      => $this->frequency_km,
            'frequency_months'  => $this->frequency_months,
            'last_service_date' => $this->last_service_date?->toDateString(),
            'last_service_km'   => $this->last_service_km,
            'next_due_date'     => $this->next_due_date?->toDateString(),
            'next_due_km'       => $this->next_due_km,
            'status'            => $this->status,
            'is_overdue'        => $this->isOverdue(),
            'is_due_soon'       => $this->isDueSoon(),
            'notes'             => $this->notes,
            'vehicle'           => $this->whenLoaded('vehicle', fn() => [
                'uuid'          => $this->vehicle->uuid,
                'plate_number'  => $this->vehicle->plate_number,
                'brand'         => $this->vehicle->brand,
                'model'         => $this->vehicle->model,
            ]),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
