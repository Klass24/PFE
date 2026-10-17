<?php

namespace App\Modules\Incident\Repositories;

use App\Modules\Incident\Models\Incident;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IncidentRepositoryInterface
{
    public function paginateByMission(int $missionId, int $perPage = 15): LengthAwarePaginator;
    public function findByUuid(string $uuid): Incident;
    public function create(array $data): Incident;
    public function update(Incident $incident, array $data): Incident;
    public function delete(Incident $incident): void;
}
