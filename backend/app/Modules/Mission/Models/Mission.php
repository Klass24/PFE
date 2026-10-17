<?php

namespace App\Modules\Mission\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Mission extends Model
{
    use SoftDeletes;
    use \App\Core\Traits\Auditable;

    protected $fillable = [
        'uuid', 'company_id', 'vehicle_id', 'driver_id',
        'origin', 'destination',
        'planned_departure', 'planned_arrival', 'actual_departure', 'actual_arrival',
        'start_date', 'end_date', 'status', 'notes', 'total_cost',
    ];

    protected function casts(): array
    {
        return [
            'start_date'        => 'datetime',
            'end_date'          => 'datetime',
            'planned_departure' => 'datetime',
            'planned_arrival'   => 'datetime',
            'actual_departure'  => 'datetime',
            'actual_arrival'    => 'datetime',
            'total_cost'        => 'decimal:2',
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

    public function vehicle()
    {
        return $this->belongsTo(\App\Modules\Vehicle\Models\Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(\App\Modules\Driver\Models\Driver::class);
    }

    public function expenses()
    {
        return $this->hasMany(\App\Modules\Expense\Models\Expense::class);
    }

    public function incidents()
    {
        return $this->hasMany(\App\Modules\Incident\Models\Incident::class);
    }

    public function checklist()
    {
        return $this->hasOne(\App\Modules\Checklist\Models\Checklist::class);
    }
}
