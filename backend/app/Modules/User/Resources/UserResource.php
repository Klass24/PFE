<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'uuid'       => $this->uuid,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->roles->first()?->name,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        // Afficher le nom de l'entreprise pour super_admin
        if ($this->company_name ?? null) {
            $data['company_name'] = $this->company_name;
        } elseif ($this->relationLoaded('company')) {
            $data['company_name'] = $this->company?->name;
        }

        return $data;
    }
}
