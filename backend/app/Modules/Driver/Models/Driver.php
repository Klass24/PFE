<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Driver extends Model
{
    use SoftDeletes;
    use \App\Core\Traits\Auditable;

    protected $fillable = [
        'uuid', 'company_id', 'user_id', 'license_number',
        'license_expiry', 'phone', 'status',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
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

    public function user()
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class);
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
