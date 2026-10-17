<?php

namespace App\Modules\Document\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'              => $this->uuid,
            'type'              => $this->type,
            'file_url'          => asset('storage/' . $this->file_path),
            'expiry_date'       => $this->expiry_date?->toDateString(),
            'is_expired'        => $this->isExpired(),
            'is_expiring_soon'  => $this->isExpiringSoon(),
            'documentable_type' => class_basename($this->documentable_type),
            'documentable_uuid' => $this->documentable?->uuid,
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
