<?php

namespace App\Modules\Vehicle\Services;

use App\Core\Services\BaseService;
use App\Modules\Company\Models\Company;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Vehicle\Models\VehicleStatusLog;
use App\Modules\Vehicle\Repositories\VehicleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VehicleService extends BaseService
{
    public function __construct(private VehicleRepositoryInterface $repo) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->repo->paginate($filters);
    }

    public function create(array $data): Vehicle
    {
        $user = auth()->user();
        $data['company_id'] = $user->company_id;

        // M1 — Vérification quota plan
        $company      = $user->company;
        $currentCount = Vehicle::count(); // scoped par company via global scope
        $maxVehicles  = $company->max_vehicles
            ?? (Company::PLAN_QUOTAS[$company->plan ?? 'starter']['vehicles'] ?? 5);

        if ($currentCount >= $maxVehicles) {
            throw ValidationException::withMessages([
                'company' => "Quota atteint : votre plan permet {$maxVehicles} véhicule(s) maximum.",
            ]);
        }

        $vehicle = $this->repo->create($data);
        Log::info('Vehicle created', ['vehicle_id' => $vehicle->id, 'user' => auth()->id()]);
        return $vehicle;
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $oldStatus = $vehicle->status;

        $vehicle = $this->repo->update($vehicle, $data);

        // M2 — Journaliser chaque changement de statut
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            VehicleStatusLog::create([
                'vehicle_id' => $vehicle->id,
                'company_id' => $vehicle->company_id,
                'old_status' => $oldStatus,
                'new_status' => $data['status'],
                'changed_by' => auth()->id(),
            ]);
            Log::info('Vehicle status changed', [
                'vehicle_id' => $vehicle->id,
                'from'       => $oldStatus,
                'to'         => $data['status'],
            ]);
        }

        Log::info('Vehicle updated', ['vehicle_id' => $vehicle->id, 'user' => auth()->id()]);
        return $vehicle;
    }

    public function delete(Vehicle $vehicle): void
    {
        $this->repo->delete($vehicle);
        Log::info('Vehicle deleted', ['vehicle_id' => $vehicle->id, 'user' => auth()->id()]);
    }

    public function uploadImage(Vehicle $vehicle, \Illuminate\Http\UploadedFile $file): Vehicle
    {
        if ($vehicle->image) {
            Storage::disk('public')->delete($vehicle->image);
        }
        $path = $file->store('vehicles', 'public');
        return $this->repo->update($vehicle, ['image' => $path]);
    }
}
