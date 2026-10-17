<?php

namespace App\Modules\Incident\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'        => $this->uuid,
            'type'        => $this->type,
            'description' => $this->description,
            'latitude'    => $this->latitude,
            'longitude'   => $this->longitude,
            'image_url'   => $this->image ? asset('storage/' . $this->image) : null,
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
