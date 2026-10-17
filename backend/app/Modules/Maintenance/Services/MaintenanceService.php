<?php

namespace App\Modules\Maintenance\Services;

use App\Core\Services\BaseService;
use App\Modules\Maintenance\Models\MaintenanceRecord;
use App\Modules\Maintenance\Models\MaintenanceSchedule;
use App\Modules\Maintenance\Repositories\MaintenanceRepositoryInterface;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class MaintenanceService extends BaseService
{
    public function __construct(private MaintenanceRepositoryInterface $repo) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->repo->paginate($filters);
    }

    public function create(array $data): MaintenanceSchedule
    {
        $vehicle = Vehicle::where('uuid', $data['vehicle_uuid'])->firstOrFail();
        $data['vehicle_id']  = $vehicle->id;
        $data['company_id']  = auth()->user()->company_id;
        unset($data['vehicle_uuid']);

        // Auto-calculate next due date
        if (isset($data['frequency_months']) && isset($data['last_service_date'])) {
            $data['next_due_date'] = \Carbon\Carbon::parse($data['last_service_date'])
                ->addMonths((int) $data['frequency_months'])->toDateString();
        }
        if (isset($data['frequency_km']) && isset($data['last_service_km'])) {
            $data['next_due_km'] = (int) $data['last_service_km'] + (int) $data['frequency_km'];
        }

        $schedule = $this->repo->create($data);
        Log::info('Maintenance schedule created', ['id' => $schedule->id, 'vehicle' => $vehicle->id]);
        return $schedule;
    }

    public function update(MaintenanceSchedule $schedule, array $data): MaintenanceSchedule
    {
        return $this->repo->update($schedule, $data);
    }

    public function delete(MaintenanceSchedule $schedule): void
    {
        $this->repo->delete($schedule);
        Log::info('Maintenance schedule deleted', ['id' => $schedule->id]);
    }

    public function completeService(MaintenanceSchedule $schedule, array $data): MaintenanceRecord
    {
        $record = MaintenanceRecord::create([
            'maintenance_schedule_id' => $schedule->id,
            'vehicle_id'              => $schedule->vehicle_id,
            'company_id'              => $schedule->company_id,
            'service_date'            => $data['service_date'],
            'service_km'              => $data['service_km'],
            'cost'                    => $data['cost'] ?? 0,
            'provider'                => $data['provider'] ?? null,
            'notes'                   => $data['notes'] ?? null,
            'performed_by'            => auth()->id(),
        ]);

        // Update schedule with new next dates
        $updateData = [
            'last_service_date' => $data['service_date'],
            'last_service_km'   => $data['service_km'],
            'status'            => 'active',
        ];

        if ($schedule->frequency_months) {
            $updateData['next_due_date'] = \Carbon\Carbon::parse($data['service_date'])
                ->addMonths($schedule->frequency_months)->toDateString();
        }
        if ($schedule->frequency_km) {
            $updateData['next_due_km'] = $data['service_km'] + $schedule->frequency_km;
        }

        $schedule->update($updateData);

        // Update vehicle mileage if higher
        $vehicle = $schedule->vehicle;
        if ($vehicle && $data['service_km'] > $vehicle->mileage) {
            $vehicle->update(['mileage' => $data['service_km']]);
        }

        Log::info('Maintenance completed', ['schedule' => $schedule->id, 'record' => $record->id]);
        return $record;
    }

    public function getOverdueSchedules(): \Illuminate\Database\Eloquent\Collection
    {
        return MaintenanceSchedule::with('vehicle')
            ->where(function ($q) {
                $q->where('next_due_date', '<=', now())
                  ->orWhereRaw('next_due_km <= (SELECT mileage FROM vehicles WHERE vehicles.id = maintenance_schedules.vehicle_id)');
            })
            ->where('status', '!=', 'completed')
            ->get();
    }

    public function getDueSoon(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return MaintenanceSchedule::with('vehicle')
            ->where('next_due_date', '<=', now()->addDays($days))
            ->where('next_due_date', '>', now())
            ->where('status', 'active')
            ->orderBy('next_due_date')
            ->get();
    }
}
