<?php

namespace App\Modules\Subscription\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'plan'            => $this->plan,
            'start_date'      => $this->start_date?->toDateString(),
            'end_date'        => $this->end_date?->toDateString(),
            'billing_status'  => $this->billing_status,
            'payment_gateway' => $this->payment_gateway,
            'amount'          => (float) ($this->amount ?? 0),
            'currency'        => $this->currency ?? 'EUR',
            'billing_cycle'   => $this->billing_cycle,
            'next_billing_at' => $this->next_billing_at?->toIso8601String(),
            'cancelled_at'    => $this->cancelled_at?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
