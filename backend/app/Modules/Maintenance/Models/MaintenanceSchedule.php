<?php

namespace App\Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MaintenanceSchedule extends Model
{
    protected $fillable = [
        'uuid', 'vehicle_id', 'company_id', 'maintenance_type',
        'frequency_km', 'frequency_months', 'last_service_date',
        'last_service_km', 'next_due_date', 'next_due_km',
        'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_service_date' => 'date',
            'next_due_date'     => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) $model->uuid = Str::uuid();
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
        });

        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check()) {
                $query->where('maintenance_schedules.company_id', auth()->user()->company_id);
            }
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Vehicle\Models\Vehicle::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function isOverdue(): bool
    {
        if ($this->next_due_date && $this->next_due_date->isPast()) return true;
        if ($this->next_due_km && $this->vehicle && $this->vehicle->mileage >= $this->next_due_km) return true;
        return false;
    }

    public function isDueSoon(int $days = 30, int $km = 500): bool
    {
        if ($this->next_due_date && $this->next_due_date->lte(now()->addDays($days))) return true;
        if ($this->next_due_km && $this->vehicle && ($this->next_due_km - $this->vehicle->mileage) <= $km) return true;
        return false;
    }
}
