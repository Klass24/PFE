<?php

namespace App\Modules\Payment\Models;

use App\Modules\Company\Models\Company;
use App\Modules\Company\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'uuid', 'company_id', 'subscription_id', 'gateway',
        'gateway_payment_id', 'gateway_invoice_id',
        'amount', 'currency', 'status', 'payment_method',
        'description', 'gateway_metadata', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'           => 'decimal:2',
            'gateway_metadata' => 'array',
            'paid_at'          => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }

    public function markSucceeded(): void
    {
        $this->update(['status' => 'succeeded', 'paid_at' => now()]);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
