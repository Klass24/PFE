<?php

namespace App\Modules\Driver\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'uuid'           => $this->uuid,
            'license_number' => $this->license_number,
            'license_expiry' => $this->license_expiry?->toDateString(),
            'phone'          => $this->phone,
            'status'         => $this->status,
            'user'           => $this->whenLoaded('user', fn() => [
                'uuid'  => $this->user->uuid,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
