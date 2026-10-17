<?php

namespace App\Modules\Audit\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'            => $this->uuid,
            'action'          => $this->action,
            'auditable_type'  => class_basename($this->auditable_type),
            'auditable_id'    => $this->auditable_id,
            'old_values'      => $this->old_values,
            'new_values'      => $this->new_values,
            'user'            => $this->whenLoaded('user', fn() => [
                'uuid' => $this->user->uuid,
                'name' => $this->user->name,
            ]),
            'ip_address'      => $this->ip_address,
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
