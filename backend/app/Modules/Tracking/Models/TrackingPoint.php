<?php

namespace App\Modules\Tracking\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TrackingPoint extends Model
{
    protected $fillable = [
        'uuid', 'mission_id', 'company_id',
        'latitude', 'longitude', 'altitude', 'accuracy',
        'speed', 'heading', 'recorded_at', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'    => 'decimal:8',
            'longitude'   => 'decimal:8',
            'speed'       => 'decimal:2',
            'recorded_at' => 'datetime',
            'synced_at'   => 'datetime',
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
                $query->where('tracking_points.company_id', auth()->user()->company_id);
            }
        });
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Mission\Models\Mission::class);
    }
}
