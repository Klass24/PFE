<?php

namespace App\Modules\Incident\Policies;

use App\Modules\Incident\Models\Incident;
use App\Modules\User\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'driver']);
    }

    public function view(User $user, Incident $incident): bool
    {
        return $incident->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'driver']);
    }

    public function update(User $user, Incident $incident): bool
    {
        return $user->hasAnyRole(['admin', 'manager'])
            && $incident->company_id === $user->company_id;
    }

    public function delete(User $user, Incident $incident): bool
    {
        return $user->hasRole('admin')
            && $incident->company_id === $user->company_id;
    }
}
