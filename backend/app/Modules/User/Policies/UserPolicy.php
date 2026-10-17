<?php

namespace App\Modules\User\Policies;

use App\Modules\User\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'manager']);
    }

    public function view(User $user, User $target): bool
    {
        // Un utilisateur peut voir son propre profil
        if ($user->id === $target->id) {
            return true;
        }

        // Super admin : peut voir les admin/manager de toute entreprise
        if ($user->hasRole('super_admin')) {
            return $target->hasAnyRole(['admin', 'manager']);
        }

        return $user->hasAnyRole(['admin', 'manager'])
            && $target->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $target): bool
    {
        // Un utilisateur peut modifier son propre profil
        if ($user->id === $target->id) {
            return true;
        }
        return $user->hasRole('admin')
            && $target->company_id === $user->company_id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasRole('admin')
            && $target->company_id === $user->company_id
            && $user->id !== $target->id; // Ne peut pas se supprimer soi-même
    }
}
