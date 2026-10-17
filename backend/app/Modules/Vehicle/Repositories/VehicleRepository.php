<?php

namespace App\Modules\Vehicle\Repositories;

use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VehicleRepository implements VehicleRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? $perPage), 100);
        return Vehicle::query()
            ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['brand']),  fn($q) => $q->where('brand', $filters['brand']))
            ->when(!empty($filters['model']),  fn($q) => $q->where('model', 'like', "%{$filters['model']}%"))
            ->when(!empty($filters['type']),  fn($q) => $q->where('type', $filters['type']))
            ->when(!empty($filters['fuel_type']),  fn($q) => $q->where('fuel_type', $filters['fuel_type']))
            ->when(!empty($filters['plate_number']), fn($q) => $q->where('plate_number', 'like', "%{$filters['plate_number']}%"))
            ->when(!empty($filters['search']), fn($q) => $q->where(function ($q) use ($filters) {
                $q->where('plate_number', 'like', "%{$filters['search']}%")
                  ->orWhere('brand', 'like', "%{$filters['search']}%")
                  ->orWhere('model', 'like', "%{$filters['search']}%");
            }))
            ->when(!empty($filters['from']), fn($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(!empty($filters['to']),   fn($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): Vehicle
    {
        return Vehicle::where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): Vehicle
    {
        return Vehicle::create($data);
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $vehicle->update($data);
        return $vehicle->fresh();
    }

    public function delete(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }
}
