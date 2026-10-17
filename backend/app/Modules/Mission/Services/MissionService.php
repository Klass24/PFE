<?php

namespace App\Modules\Mission\Services;

use App\Core\Services\BaseService;
use App\Modules\Checklist\Repositories\ChecklistRepositoryInterface;
use App\Modules\Mission\Events\MissionCompletedEvent;
use App\Modules\Mission\Events\MissionStartedEvent;
use App\Modules\Mission\Models\Mission;
use App\Modules\Mission\Repositories\MissionRepositoryInterface;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MissionService extends BaseService
{
    public function __construct(
        private MissionRepositoryInterface    $repo,
        private ChecklistRepositoryInterface  $checklistRepo,
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->repo->paginate($filters);
    }

    public function create(array $data): Mission
    {
        $companyId = auth()->user()->company_id;
        $data['company_id'] = $companyId;

        $this->checkOverlap(
            $data['vehicle_id'],
            $data['driver_id'],
            $data['start_date'],
            $data['end_date'] ?? null
        );

        $this->checkDriverLicense($data['driver_id']);
        $this->checkVehicleAvailability($data['vehicle_id']);

        $mission = $this->repo->create($data);

        // Créer automatiquement la check-list de départ
        $this->checklistRepo->createForMission($mission);

        Log::info('Mission created', ['mission_id' => $mission->id, 'user' => auth()->id()]);
        return $mission;
    }

    public function update(Mission $mission, array $data): Mission
    {
        if (isset($data['vehicle_id']) || isset($data['driver_id']) || isset($data['start_date'])) {
            $this->checkOverlap(
                $data['vehicle_id'] ?? $mission->vehicle_id,
                $data['driver_id']  ?? $mission->driver_id,
                $data['start_date'] ?? $mission->start_date,
                $data['end_date']   ?? $mission->end_date,
                $mission->id
            );
        }
        return $this->repo->update($mission, $data);
    }

    public function delete(Mission $mission): void
    {
        $this->repo->delete($mission);
    }

    public function start(Mission $mission): Mission
    {
        // Règle M4 : la check-list doit être validée avant le démarrage
        $checklist = $this->checklistRepo->findByMission($mission->id);

        if (!$checklist || $checklist->status !== 'validated') {
            throw ValidationException::withMessages([
                'checklist' => 'La check-list de sécurité doit être validée avant de démarrer la mission.',
            ]);
        }

        $mission = $this->repo->update($mission, [
            'status'           => 'in_progress',
            'actual_departure' => now(),
        ]);

        $mission->vehicle->update(['status' => 'on_mission']);
        $mission->driver->update(['status' => 'on_mission']);

        event(new MissionStartedEvent($mission));
        Log::info('Mission started', ['mission_id' => $mission->id]);
        return $mission;
    }

    public function complete(Mission $mission): Mission
    {
        $totalCost = (float) $mission->expenses()->sum('amount');

        $mission = $this->repo->update($mission, [
            'status'         => 'completed',
            'end_date'       => now(),
            'actual_arrival' => now(),
            'total_cost'     => $totalCost,
        ]);

        $mission->vehicle->update(['status' => 'available', 'current_driver_id' => null]);
        $mission->driver->update(['status' => 'available']);

        event(new MissionCompletedEvent($mission));
        Log::info('Mission completed', ['mission_id' => $mission->id, 'total_cost' => $totalCost]);
        return $mission;
    }

    public function cancel(Mission $mission): Mission
    {
        $mission = $this->repo->update($mission, ['status' => 'cancelled']);
        $mission->vehicle->update(['status' => 'available', 'current_driver_id' => null]);
        $mission->driver->update(['status' => 'available']);
        Log::info('Mission cancelled', ['mission_id' => $mission->id]);
        return $mission;
    }

    // -------------------------------------------------------------------------
    //  Règles métier privées
    // -------------------------------------------------------------------------

    /**
     * Vérifie qu'il n'y a pas de chevauchement de dates pour le véhicule ou le chauffeur.
     */
    private function checkOverlap(
        int $vehicleId,
        int $driverId,
        $startDate,
        $endDate = null,
        ?int $excludeMissionId = null
    ): void {
        $query = Mission::withoutGlobalScopes()
            ->whereIn('status', ['planned', 'in_progress'])
            ->where(function ($q) use ($startDate, $endDate) {
                // Chevauchement : start A < end B && start B < end A
                if ($endDate) {
                    $q->where('start_date', '<', $endDate)
                      ->where(fn ($qq) => $qq->whereNull('end_date')->orWhere('end_date', '>', $startDate));
                } else {
                    $q->where(fn ($qq) => $qq->whereNull('end_date')->orWhere('end_date', '>', $startDate));
                }
            });

        if ($excludeMissionId) {
            $query->where('id', '!=', $excludeMissionId);
        }

        $vehicleConflict = (clone $query)->where('vehicle_id', $vehicleId)->exists();
        if ($vehicleConflict) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Ce véhicule est déjà assigné sur la période demandée.',
            ]);
        }

        $driverConflict = (clone $query)->where('driver_id', $driverId)->exists();
        if ($driverConflict) {
            throw ValidationException::withMessages([
                'driver_id' => 'Ce chauffeur est déjà assigné sur la période demandée.',
            ]);
        }
    }

    /**
     * Vérifie que le permis du chauffeur n'est pas expiré.
     */
    private function checkDriverLicense(int $driverId): void
    {
        $driver = \App\Modules\Driver\Models\Driver::withoutGlobalScopes()->find($driverId);

        if ($driver && $driver->license_expiry < now()->toDateString()) {
            throw ValidationException::withMessages([
                'driver_id' => 'Le permis de conduire de ce chauffeur est expiré. Mission bloquée.',
            ]);
        }
    }

    /**
     * Vérifie que le véhicule est disponible (pas en mission ni en maintenance).
     */
    private function checkVehicleAvailability(int $vehicleId): void
    {
        $vehicle = Vehicle::withoutGlobalScopes()->find($vehicleId);

        if ($vehicle && in_array($vehicle->status, ['on_mission', 'maintenance', 'inactive'])) {
            throw ValidationException::withMessages([
                'vehicle_id' => "Ce véhicule n'est pas disponible (statut : {$vehicle->status}).",
            ]);
        }
    }
}
