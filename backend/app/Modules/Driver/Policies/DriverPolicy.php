<?php

namespace App\Modules\Driver\Policies;

use App\Modules\Driver\Models\Driver;
use App\Modules\User\Models\User;

class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function view(User $user, Driver $driver): bool
    {
        return $driver->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Driver $driver): bool
    {
        return $user->hasAnyRole(['admin', 'manager'])
            && $driver->company_id === $user->company_id;
    }

    public function delete(User $user, Driver $driver): bool
    {
        return $user->hasRole('admin')
            && $driver->company_id === $user->company_id;
    }
}
