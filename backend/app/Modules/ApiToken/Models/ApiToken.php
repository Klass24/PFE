<?php

namespace App\Modules\ApiToken\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'uuid', 'company_id', 'name', 'token', 'abilities', 'last_used_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'abilities'    => 'array',
            'last_used_at' => 'datetime',
            'expires_at'   => 'datetime',
        ];
    }

    protected $hidden = ['token'];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(\App\Modules\Company\Models\Company::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
