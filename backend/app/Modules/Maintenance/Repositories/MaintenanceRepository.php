<?php

namespace App\Modules\Maintenance\Repositories;

use App\Modules\Maintenance\Models\MaintenanceSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MaintenanceRepository implements MaintenanceRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return MaintenanceSchedule::with('vehicle')
            ->when($filters['vehicle_uuid'] ?? null, function ($q, $uuid) {
                $q->whereHas('vehicle', fn($v) => $v->where('uuid', $uuid));
            })
            ->when($filters['vehicle_search'] ?? null, function ($q, $value) {
                $q->whereHas('vehicle', function ($v) use ($value) {
                    $v->where('plate_number', 'like', "%{$value}%")
                      ->orWhere('brand', 'like', "%{$value}%")
                      ->orWhere('model', 'like', "%{$value}%");
                });
            })
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['maintenance_type'] ?? null, fn($q, $t) => $q->where('maintenance_type', $t))
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('next_due_date', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('next_due_date', '<=', $v))
            ->when($filters['is_overdue'] ?? null, function ($q, $value) {
                if (!filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                    return;
                }

                $q->where(function ($sq) {
                    $sq->whereDate('next_due_date', '<', now()->toDateString())
                        ->orWhereRaw('next_due_km <= (SELECT mileage FROM vehicles WHERE vehicles.id = maintenance_schedules.vehicle_id)');
                });
            })
            ->orderBy('next_due_date')
            ->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function create(array $data): MaintenanceSchedule
    {
        return MaintenanceSchedule::create($data);
    }

    public function update(MaintenanceSchedule $schedule, array $data): MaintenanceSchedule
    {
        $schedule->update($data);
        return $schedule->refresh();
    }

    public function delete(MaintenanceSchedule $schedule): void
    {
        $schedule->delete();
    }
}
