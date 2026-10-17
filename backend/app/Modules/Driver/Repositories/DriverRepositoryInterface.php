<?php

namespace App\Modules\Driver\Repositories;

use App\Modules\Driver\Models\Driver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DriverRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function findByUuid(string $uuid): Driver;
    public function create(array $data): Driver;
    public function update(Driver $driver, array $data): Driver;
    public function delete(Driver $driver): void;
}
