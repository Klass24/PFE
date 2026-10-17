<?php

namespace App\Modules\Notification\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid'       => $this->uuid,
            'type'       => $this->type,
            'message'    => $this->message,
            'channel'    => $this->channel,
            'is_read'    => $this->is_read,
            'sent_at'    => $this->sent_at?->toIso8601String(),
            'read_at'    => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
