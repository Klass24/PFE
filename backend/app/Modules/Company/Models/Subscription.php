<?php

namespace App\Modules\Company\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscription extends Model
{
    protected $fillable = [
        'company_id', 'plan', 'start_date', 'end_date', 'billing_status',
        'payment_gateway', 'gateway_subscription_id', 'gateway_customer_id',
        'amount', 'currency', 'billing_cycle', 'next_billing_at',
        'cancelled_at', 'gateway_metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_date'      => 'date',
            'end_date'        => 'date',
            'next_billing_at' => 'datetime',
            'cancelled_at'    => 'datetime',
            'amount'          => 'decimal:2',
            'gateway_metadata'=> 'array',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payments()
    {
        return $this->hasMany(\App\Modules\Payment\Models\Payment::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->billing_status === 'active';
    }

    public function isPaid(): bool
    {
        return in_array($this->payment_gateway, ['stripe', 'paypal']);
    }

    public function isManual(): bool
    {
        return $this->payment_gateway === 'manual' || $this->payment_gateway === null;
    }

    public function cancel(): void
    {
        $this->update([
            'billing_status' => 'cancelled',
            'cancelled_at'   => now(),
            'end_date'       => now(),
        ]);
    }
}
