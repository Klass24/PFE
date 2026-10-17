<?php

namespace App\Modules\Pricing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $fillable = [
        'uuid', 'name', 'slug', 'price_monthly', 'price_yearly', 'currency',
        'max_vehicles', 'max_drivers', 'max_users', 'features',
        'is_active', 'is_popular', 'sort_order', 'trial_days',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly'  => 'decimal:2',
            'max_vehicles'  => 'integer',
            'max_drivers'   => 'integer',
            'max_users'     => 'integer',
            'features'      => 'array',
            'is_active'     => 'boolean',
            'is_popular'    => 'boolean',
            'sort_order'    => 'integer',
            'trial_days'    => 'integer',
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

    // ─── Relations ────────────────────────────────────────────────────────────

    public function companies()
    {
        return $this->hasMany(\App\Modules\Company\Models\Company::class, 'plan_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isFree(): bool
    {
        return $this->price_monthly <= 0;
    }

    public function yearlySavingPercent(): int
    {
        if ($this->price_monthly <= 0) {
            return 0;
        }
        $yearlyFromMonthly = $this->price_monthly * 12;
        return (int) round((1 - $this->price_yearly / $yearlyFromMonthly) * 100);
    }
}
