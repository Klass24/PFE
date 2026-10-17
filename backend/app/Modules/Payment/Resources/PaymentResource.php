<?php

namespace App\Modules\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'               => $this->uuid,
            'gateway'            => $this->gateway,
            'gateway_payment_id' => $this->gateway_payment_id,
            'amount'             => (float) $this->amount,
            'currency'           => $this->currency,
            'status'             => $this->status,
            'payment_method'     => $this->payment_method,
            'description'        => $this->description,
            'paid_at'            => $this->paid_at?->toIso8601String(),
            'company'            => $this->when($this->relationLoaded('company'), fn () => [
                'uuid' => $this->company->uuid,
                'name' => $this->company->name,
            ]),
            'subscription_plan'  => $this->when($this->relationLoaded('subscription'), fn () => $this->subscription?->plan),
            'created_at'         => $this->created_at->toIso8601String(),
        ];
    }
}
