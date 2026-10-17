<?php

namespace App\Modules\Vehicle\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'plate_number'  => $this->plate_number,
            'chassis_number'        => $this->chassis_number,
            'type'          => $this->type,
            'brand'         => $this->brand,
            'model'         => $this->model,
            'year'          => $this->year,
            'mileage'       => $this->mileage,
            'fuel_type'     => $this->fuel_type,
            'status'        => $this->status,
            'image_url'     => $this->image ? asset('storage/' . $this->image) : null,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
