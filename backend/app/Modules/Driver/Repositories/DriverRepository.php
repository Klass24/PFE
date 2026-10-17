<?php

namespace App\Modules\Driver\Repositories;

use App\Modules\Driver\Models\Driver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DriverRepository implements DriverRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? $perPage), 100);
        return Driver::query()
            ->with('user:id,uuid,name,email')
            ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['license_number']), fn($q) => $q->where('license_number', 'like', "%{$filters['license_number']}%"))
            ->when(!empty($filters['phone']), fn($q) => $q->where('phone', 'like', "%{$filters['phone']}%"))
            ->when(!empty($filters['name']), fn($q) => $q->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['name']}%");
            }))
            ->when(!empty($filters['email']), fn($q) => $q->whereHas('user', function ($q) use ($filters) {
                $q->where('email', 'like', "%{$filters['email']}%");
            }))
            ->when(!empty($filters['search']), fn($q) => $q->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%");
            }))
            ->when(!empty($filters['from']), fn($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(!empty($filters['to']),   fn($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): Driver
    {
        return Driver::where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): Driver
    {
        return Driver::create($data);
    }

    public function update(Driver $driver, array $data): Driver
    {
        $driver->update($data);
        return $driver->fresh();
    }

    public function delete(Driver $driver): void
    {
        $driver->delete();
    }
}
