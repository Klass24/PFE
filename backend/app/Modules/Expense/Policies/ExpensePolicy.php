<?php

namespace App\Modules\Expense\Policies;

use App\Modules\Expense\Models\Expense;
use App\Modules\User\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'driver']);
    }

    public function update(User $user, Expense $expense): bool
    {
        return ($user->hasAnyRole(['admin', 'manager']) && $expense->company_id === $user->company_id)
            || ($user->hasRole('driver') && $expense->created_by === $user->id);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->hasAnyRole(['admin', 'manager'])
            && $expense->company_id === $user->company_id;
    }
}
