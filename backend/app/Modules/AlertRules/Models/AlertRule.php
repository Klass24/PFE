<?php

namespace App\Modules\AlertRules\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AlertRule extends Model
{
    protected $fillable = [
        'uuid', 'company_id', 'name', 'description',
        'trigger_type', 'conditions', 'actions',
        'priority', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions'    => 'array',
            'is_active'  => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) $model->uuid = Str::uuid();
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
            if (auth()->check() && empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });

        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check()) {
                $query->where('alert_rules.company_id', auth()->user()->company_id);
            }
        });
    }

    public function evaluate(array $triggerData): bool
    {
        if (empty($this->conditions)) return true;

        foreach ($this->conditions as $field => $condition) {
            $operator = $condition['operator'] ?? '==';
            $value    = $condition['value'] ?? null;
            $actual   = $triggerData[$field] ?? null;

            if ($actual === null) return false;

            $match = match ($operator) {
                '>'  => $actual > $value,
                '>=' => $actual >= $value,
                '<'  => $actual < $value,
                '<=' => $actual <= $value,
                '==' => $actual == $value,
                '!=' => $actual != $value,
                'in' => is_array($value) && in_array($actual, $value),
                default => false,
            };

            if (!$match) return false;
        }

        return true;
    }
}
