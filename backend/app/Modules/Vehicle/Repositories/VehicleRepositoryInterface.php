<?php

namespace App\Modules\Vehicle\Repositories;

use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VehicleRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function findByUuid(string $uuid): Vehicle;
    public function create(array $data): Vehicle;
    public function update(Vehicle $vehicle, array $data): Vehicle;
    public function delete(Vehicle $vehicle): void;
}
