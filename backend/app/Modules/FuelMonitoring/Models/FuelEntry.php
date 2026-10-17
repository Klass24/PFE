<?php

namespace App\Modules\FuelMonitoring\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FuelEntry extends Model
{
    protected $fillable = [
        'uuid', 'vehicle_id', 'company_id', 'mission_id',
        'liters', 'cost_per_liter', 'total_cost', 'odometer_reading',
        'station_name', 'latitude', 'longitude', 'recorded_at',
        'sync_status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'liters'          => 'decimal:2',
            'cost_per_liter'  => 'decimal:2',
            'total_cost'      => 'decimal:2',
            'recorded_at'     => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) $model->uuid = Str::uuid();
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });

        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check()) {
                $query->where('fuel_entries.company_id', auth()->user()->company_id);
            }
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Vehicle\Models\Vehicle::class);
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Mission\Models\Mission::class);
    }
}
