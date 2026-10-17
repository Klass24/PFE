<?php

namespace App\Modules\Checklist\Policies;

use App\Modules\Checklist\Models\Checklist;
use App\Modules\User\Models\User;

class ChecklistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'driver']);
    }

    public function view(User $user, Checklist $checklist): bool
    {
        return $checklist->mission->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'driver']);
    }

    public function update(User $user, Checklist $checklist): bool
    {
        return $checklist->mission->company_id === $user->company_id
            && $user->hasAnyRole(['admin', 'manager', 'driver']);
    }

    public function validate(User $user, Checklist $checklist): bool
    {
        return $user->hasAnyRole(['admin', 'manager'])
            && $checklist->mission->company_id === $user->company_id;
    }
}
