<?php

namespace App\Modules\Incident\Repositories;

use App\Modules\Incident\Models\Incident;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IncidentRepository implements IncidentRepositoryInterface
{
    public function paginateByMission(int $missionId, int $perPage = 15): LengthAwarePaginator
    {
        return Incident::where('mission_id', $missionId)->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findByUuid(string $uuid): Incident
    {
        return Incident::where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): Incident
    {
        return Incident::create($data);
    }

    public function update(Incident $incident, array $data): Incident
    {
        $incident->update($data);
        return $incident->fresh();
    }

    public function delete(Incident $incident): void
    {
        $incident->delete();
    }
}
