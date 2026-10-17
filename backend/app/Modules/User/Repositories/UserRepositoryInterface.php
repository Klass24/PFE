<?php

namespace App\Modules\User\Repositories;

use App\Modules\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function findByEmail(string $email): ?User;
    public function findByUuid(string $uuid): User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): void;
}
