<?php

namespace App\Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use SoftDeletes;
    use \App\Core\Traits\Auditable;

    protected $fillable = [
        'uuid', 'company_id', 'plate_number', 'chassis_number', 'type',
        'brand', 'model', 'year', 'mileage', 'fuel_type', 'status',
        'image', 'current_driver_id',
    ];

    protected function casts(): array
    {
        return [
            'year'    => 'integer',
            'mileage' => 'integer',
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

    public function company()
    {
        return $this->belongsTo(\App\Modules\Company\Models\Company::class);
    }

    public function missions()
    {
        return $this->hasMany(\App\Modules\Mission\Models\Mission::class);
    }

    public function documents()
    {
        return $this->morphMany(\App\Modules\Document\Models\Document::class, 'documentable');
    }
}
