<?php

namespace App\Modules\Checklist\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Checklist extends Model
{
    protected $fillable = [
        'uuid', 'mission_id', 'driver_id', 'vehicle_id', 'company_id',
        'status', 'submitted_at', 'device_id',
        'date_saisie_reelle', 'date_reception_serveur', 'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at'           => 'datetime',
            'date_saisie_reelle'     => 'datetime',
            'date_reception_serveur' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check()) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    public function mission()
    {
        return $this->belongsTo(\App\Modules\Mission\Models\Mission::class);
    }

    public function driver()
    {
        return $this->belongsTo(\App\Modules\Driver\Models\Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(\App\Modules\Vehicle\Models\Vehicle::class);
    }

    public function items()
    {
        return $this->hasMany(ChecklistItem::class);
    }

    public function isFullyCompleted(): bool
    {
        return $this->items()
            ->where(function ($q) {
                $q->where('requires_photo', true)->whereNull('photo_path');
            })
            ->doesntExist();
    }
}
