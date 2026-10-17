<?php

namespace App\Modules\Incident\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Incident extends Model
{
    protected $fillable = [
        'uuid', 'company_id', 'mission_id', 'type',
        'description', 'latitude', 'longitude', 'image',
        'reported_at', 'date_reception_serveur', 'device_id', 'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'latitude'               => 'decimal:8',
            'longitude'              => 'decimal:8',
            'reported_at'            => 'datetime',
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
}
