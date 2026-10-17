<?php

namespace App\Modules\Mission\Policies;

use App\Modules\Mission\Models\Mission;
use App\Modules\User\Models\User;

class MissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'driver']);
    }

    public function view(User $user, Mission $mission): bool
    {
        if ($user->hasRole('driver')) {
            return $mission->driver?->user_id === $user->id;
        }
        return $mission->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Mission $mission): bool
    {
        return $user->hasAnyRole(['admin', 'manager'])
            && $mission->company_id === $user->company_id;
    }

    public function delete(User $user, Mission $mission): bool
    {
        return $user->hasRole('admin')
            && $mission->company_id === $user->company_id;
    }
}
