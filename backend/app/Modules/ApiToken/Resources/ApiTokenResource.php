<?php

namespace App\Modules\ApiToken\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'         => $this->uuid,
            'name'         => $this->name,
            'abilities'    => $this->abilities,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'expires_at'   => $this->expires_at?->toIso8601String(),
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}
