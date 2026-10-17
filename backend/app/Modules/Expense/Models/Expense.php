<?php

namespace App\Modules\Expense\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Expense extends Model
{
    use \App\Core\Traits\Auditable;

    protected $fillable = [
        'uuid', 'company_id', 'mission_id', 'type', 'amount',
        'liters', 'mileage', 'receipt_image', 'notes', 'created_by',
        'date_saisie_reelle', 'date_reception_serveur', 'device_id', 'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'amount'                 => 'decimal:2',
            'liters'                 => 'decimal:2',
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
            if (auth()->check()) {
                if (empty($model->company_id)) {
                    $model->company_id = auth()->user()->company_id;
                }
                if (empty($model->created_by)) {
                    $model->created_by = auth()->id();
                }
            }
        });
    }

    public function mission()
    {
        return $this->belongsTo(\App\Modules\Mission\Models\Mission::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class, 'created_by');
    }
}
