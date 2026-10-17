<?php

namespace App\Modules\Company\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'name'       => $this->name,
            'slug'       => $this->slug,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'plan'       => $this->plan,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
