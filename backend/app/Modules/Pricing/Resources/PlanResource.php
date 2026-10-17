<?php

namespace App\Modules\Pricing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'            => $this->uuid,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'price_monthly'   => (float) $this->price_monthly,
            'price_yearly'    => (float) $this->price_yearly,
            'currency'        => $this->currency,
            'max_vehicles'    => $this->max_vehicles,
            'max_drivers'     => $this->max_drivers,
            'max_users'       => $this->max_users,
            'features'        => $this->features ?? [],
            'is_active'       => $this->is_active,
            'is_popular'      => $this->is_popular,
            'sort_order'      => $this->sort_order,
            'trial_days'      => $this->trial_days,
            'is_free'         => $this->isFree(),
            'yearly_saving'   => $this->yearlySavingPercent(),
            'companies_count' => $this->when(
                $request->user()?->hasRole('super_admin'),
                fn () => $this->companies()->count()
            ),
        ];
    }
}
