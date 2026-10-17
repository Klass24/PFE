<?php

namespace App\Modules\Mission\Repositories;

use App\Modules\Mission\Models\Mission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MissionRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function findByUuid(string $uuid): Mission;
    public function create(array $data): Mission;
    public function update(Mission $mission, array $data): Mission;
    public function delete(Mission $mission): void;
}
