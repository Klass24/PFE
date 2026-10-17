<?php

namespace App\Modules\Document\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Document extends Model
{
    protected $fillable = [
        'uuid', 'company_id', 'documentable_type', 'documentable_id',
        'type', 'file_path', 'issue_date', 'expiry_date', 'alert_sent',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'issue_date'  => 'date',
            'alert_sent'  => 'boolean',
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

    public function documentable()
    {
        return $this->morphTo();
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->isBefore(now()->addDays($days));
    }
}
