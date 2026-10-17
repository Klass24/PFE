<?php

namespace App\Modules\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'          => $this->uuid,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'plan'          => $this->plan,
            'status'        => $this->status,
            'is_active'     => $this->is_active,
            'max_vehicles'  => $this->max_vehicles,
            'max_drivers'   => $this->max_drivers,
            'max_users'     => $this->max_users,
            'users_count'   => $this->whenCounted('users'),
            'vehicles_count'=> $this->whenCounted('vehicles'),
            'active_subscription' => $this->whenLoaded('activeSubscription', function () {
                return $this->activeSubscription ? [
                    'id'             => $this->activeSubscription->id,
                    'plan'           => $this->activeSubscription->plan,
                    'start_date'     => $this->activeSubscription->start_date?->toDateString(),
                    'end_date'       => $this->activeSubscription->end_date?->toDateString(),
                    'billing_status' => $this->activeSubscription->billing_status,
                ] : null;
            }),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
