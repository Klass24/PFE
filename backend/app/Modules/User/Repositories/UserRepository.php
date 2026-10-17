<?php

namespace App\Modules\User\Repositories;

use App\Modules\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query();

        // Super admin : cross-tenant, avec jointure company
        if (auth()->user()->hasRole('super_admin')) {
            $query->join('companies', 'users.company_id', '=', 'companies.id')
                  ->select('users.*', 'companies.name as company_name');
        } else {
            $query->where('company_id', auth()->user()->company_id);
        }

        return $query
            ->when(!empty($filters['role']), function ($q) use ($filters) {
                $roles = (array) $filters['role'];
                return $q->whereHas('roles', fn($r) => $r->whereIn('name', $roles));
            })
            ->when(isset($filters['status']), fn($q) => $q->where('users.is_active', $filters['status'] === 'active'))
            ->when(!empty($filters['name']), fn($q) => $q->where('users.name', 'like', "%{$filters['name']}%"))
            ->when(!empty($filters['email']), fn($q) => $q->where('users.email', 'like', "%{$filters['email']}%"))
            ->when(!empty($filters['search']), fn($q) => $q->where(function ($q) use ($filters) {
                $q->where('users.name', 'like', "%{$filters['search']}%")
                  ->orWhere('users.email', 'like', "%{$filters['search']}%");
            }))
            ->latest('users.created_at')
            ->paginate(min((int) ($filters['per_page'] ?? 10), 100));
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByUuid(string $uuid): User
    {
        return User::where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
