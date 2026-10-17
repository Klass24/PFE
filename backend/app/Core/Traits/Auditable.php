<?php

namespace App\Core\Traits;

use App\Modules\Audit\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::logAudit($model, 'created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            if (empty($dirty)) {
                return;
            }
            $old = array_intersect_key($model->getOriginal(), $dirty);
            static::logAudit($model, 'updated', $old, $dirty);
        });

        static::deleted(function ($model) {
            static::logAudit($model, 'deleted', $model->getOriginal(), null);
        });
    }

    protected static function logAudit($model, string $action, ?array $oldValues, ?array $newValues): void
    {
        // Exclure les champs sensibles
        $hidden = ['password', 'remember_token'];
        if ($oldValues) {
            $oldValues = array_diff_key($oldValues, array_flip($hidden));
        }
        if ($newValues) {
            $newValues = array_diff_key($newValues, array_flip($hidden));
        }

        $user = auth()->user();

        AuditLog::create([
            'company_id'     => $user?->company_id ?? $model->company_id ?? null,
            'user_id'        => $user?->id,
            'action'         => $action,
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->getKey(),
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'ip_address'     => request()?->ip(),
            'user_agent'     => request()?->userAgent(),
        ]);
    }
}
