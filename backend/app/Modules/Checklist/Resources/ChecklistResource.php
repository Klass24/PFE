<?php

namespace App\Modules\Checklist\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid'                   => $this->uuid,
            'mission_id'             => $this->mission?->uuid,
            'driver_id'              => $this->driver_id,
            'vehicle_id'             => $this->vehicle_id,
            'status'                 => $this->status,
            'submitted_at'           => $this->submitted_at?->toIso8601String(),
            'date_saisie_reelle'     => $this->date_saisie_reelle?->toIso8601String(),
            'sync_status'            => $this->sync_status,
            'items'                  => $this->whenLoaded('items', fn () =>
                $this->items->map(fn ($item) => [
                    'id'             => $item->id,
                    'item_key'       => $item->item_key,
                    'label'          => $item->label,
                    'is_ok'          => $item->is_ok,
                    'requires_photo' => $item->requires_photo,
                    'has_photo'      => !is_null($item->photo_path),
                    'note'           => $item->note,
                ])
            ),
        ];
    }
}
