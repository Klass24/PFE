<?php

namespace App\Modules\Vehicle\Policies;

use App\Modules\User\Models\User;
use App\Modules\Vehicle\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'driver']);
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $vehicle->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->hasAnyRole(['admin', 'manager'])
            && $vehicle->company_id === $user->company_id;
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->hasRole('admin')
            && $vehicle->company_id === $user->company_id;
    }
}
