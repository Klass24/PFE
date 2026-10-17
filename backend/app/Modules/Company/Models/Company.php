<?php

namespace App\Modules\Company\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    /** Quota par défaut selon le plan (si max_* non renseignés en DB). */
    public const PLAN_QUOTAS = [
        'starter'    => ['vehicles' => 5,   'drivers' => 5,   'users' => 3],
        'pro'        => ['vehicles' => 20,  'drivers' => 20,  'users' => 10],
        'enterprise' => ['vehicles' => 999, 'drivers' => 999, 'users' => 999],
    ];

    protected $fillable = [
        'uuid', 'name', 'slug', 'email', 'phone', 'plan', 'plan_id',
        'is_active', 'status', 'max_vehicles', 'max_drivers', 'max_users',
    ];

    protected function casts(): array
    {
        return [
            'is_active'    => 'boolean',
            'max_vehicles' => 'integer',
            'max_drivers'  => 'integer',
            'max_users'    => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name) . '-' . Str::random(5);
            }

            // Appliquer les quotas du plan si non définis
            $plan = $model->plan ?? 'starter';
            $quotas = self::PLAN_QUOTAS[$plan] ?? self::PLAN_QUOTAS['starter'];
            $model->max_vehicles = $model->max_vehicles ?? $quotas['vehicles'];
            $model->max_drivers  = $model->max_drivers  ?? $quotas['drivers'];
            $model->max_users    = $model->max_users    ?? $quotas['users'];
        });

        static::updating(function ($model) {
            // Synchroniser is_active / status
            if ($model->isDirty('status')) {
                $model->is_active = $model->status === 'active';
            } elseif ($model->isDirty('is_active')) {
                $model->status = $model->is_active ? 'active' : 'suspended';
            }

            // Recalculer les quotas lors d'un changement de plan
            if ($model->isDirty('plan')) {
                $quotas = self::PLAN_QUOTAS[$model->plan] ?? self::PLAN_QUOTAS['starter'];
                $model->max_vehicles = $quotas['vehicles'];
                $model->max_drivers  = $quotas['drivers'];
                $model->max_users    = $quotas['users'];
            }
        });
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended', 'is_active' => false]);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active', 'is_active' => true]);
    }

    // -------------------------------------------------------------------------
    //  Relations
    // -------------------------------------------------------------------------

    public function users()
    {
        return $this->hasMany(\App\Modules\User\Models\User::class);
    }

    public function vehicles()
    {
        return $this->hasMany(\App\Modules\Vehicle\Models\Vehicle::class);
    }

    public function planRecord()
    {
        return $this->belongsTo(\App\Modules\Pricing\Models\Plan::class, 'plan_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('billing_status', 'active')
            ->latest();
    }
}
