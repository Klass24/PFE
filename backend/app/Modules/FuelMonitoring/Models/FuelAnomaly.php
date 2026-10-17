<?php

namespace App\Modules\FuelMonitoring\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FuelAnomaly extends Model
{
    protected $fillable = [
        'uuid', 'vehicle_id', 'company_id', 'mission_id',
        'anomaly_type', 'expected_consumption', 'actual_consumption',
        'deviation_percent', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_consumption' => 'decimal:2',
            'actual_consumption'   => 'decimal:2',
            'deviation_percent'    => 'decimal:2',
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
                $query->where('fuel_anomalies.company_id', auth()->user()->company_id);
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
