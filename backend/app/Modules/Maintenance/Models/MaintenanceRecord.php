<?php

namespace App\Modules\Maintenance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MaintenanceRecord extends Model
{
    protected $fillable = [
        'uuid', 'maintenance_schedule_id', 'vehicle_id', 'company_id',
        'service_date', 'service_km', 'cost', 'provider', 'notes', 'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'cost'         => 'decimal:2',
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
                $query->where('maintenance_records.company_id', auth()->user()->company_id);
            }
        });
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Vehicle\Models\Vehicle::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class, 'performed_by');
    }
}
