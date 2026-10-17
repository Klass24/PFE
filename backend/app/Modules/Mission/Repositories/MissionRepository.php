<?php

namespace App\Modules\Mission\Repositories;

use App\Modules\Mission\Models\Mission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MissionRepository implements MissionRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? $perPage), 100);
        return Mission::query()
            ->with(['vehicle:id,uuid,plate_number,brand,model', 'driver:id,uuid,license_number'])
            ->when(!empty($filters['status']),    fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['driver_id']), fn($q) => $q->where('driver_id', $filters['driver_id']))
            ->when(!empty($filters['vehicle_id']),fn($q) => $q->where('vehicle_id', $filters['vehicle_id']))
            ->when(!empty($filters['origin']),fn($q) => $q->where('origin', 'like', "%{$filters['origin']}%"))
            ->when(!empty($filters['destination']),fn($q) => $q->where('destination', 'like', "%{$filters['destination']}%"))
            ->when(!empty($filters['from']), fn($q) => $q->whereDate('start_date', '>=', $filters['from']))
            ->when(!empty($filters['to']),   fn($q) => $q->whereDate('start_date', '<=', $filters['to']))
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): Mission
    {
        return Mission::where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): Mission
    {
        return Mission::create($data);
    }

    public function update(Mission $mission, array $data): Mission
    {
        $mission->update($data);
        return $mission->fresh();
    }

    public function delete(Mission $mission): void
    {
        $mission->delete();
    }
}
