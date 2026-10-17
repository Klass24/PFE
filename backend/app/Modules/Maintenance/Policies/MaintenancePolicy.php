<?php

namespace App\Modules\Maintenance\Policies;

use App\Modules\Maintenance\Models\MaintenanceSchedule;
use App\Modules\User\Models\User;

class MaintenancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, MaintenanceSchedule $schedule): bool
    {
        return $user->hasAnyRole(['admin', 'manager'])
            && $schedule->company_id === $user->company_id;
    }

    public function delete(User $user, MaintenanceSchedule $schedule): bool
    {
        return $user->hasRole('admin')
            && $schedule->company_id === $user->company_id;
    }
}
