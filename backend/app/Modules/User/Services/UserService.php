<?php

namespace App\Modules\User\Services;

use App\Core\Services\BaseService;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserService extends BaseService
{
    public function __construct(private UserRepositoryInterface $repo) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->repo->paginate($filters);
    }

    public function create(array $data): User
    {
        $currentUser = auth()->user();
        $company = $currentUser->company;

        // M1 — Vérification quota utilisateurs
        $currentCount = User::where('company_id', $company->id)->count();
        if ($currentCount >= $company->max_users) {
            throw ValidationException::withMessages([
                'company' => "Quota atteint : votre plan permet {$company->max_users} utilisateur(s) maximum.",
            ]);
        }

        $data['company_id'] = $company->id;
        $data['password'] = Hash::make($data['password']);

        $user = $this->repo->create($data);

        // Assigner le rôle
        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }

        Log::info('User created', ['user_id' => $user->id, 'by' => $currentUser->id]);
        return $user;
    }

    public function update(User $user, array $data): User
    {
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Mise à jour du rôle
        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }

        $user = $this->repo->update($user, $data);

        Log::info('User updated', ['user_id' => $user->id, 'by' => auth()->id()]);
        return $user;
    }

    public function delete(User $user): void
    {
        // Empêcher de se supprimer soi-même
        if ($user->id === auth()->id()) {
            throw ValidationException::withMessages([
                'user' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ]);
        }

        $this->repo->delete($user);
        Log::info('User deleted', ['user_id' => $user->id, 'by' => auth()->id()]);
    }
}
