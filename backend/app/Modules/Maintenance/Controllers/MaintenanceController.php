<?php

namespace App\Modules\Maintenance\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Maintenance\Models\MaintenanceSchedule;
use App\Modules\Maintenance\Requests\CompleteMaintenanceRequest;
use App\Modules\Maintenance\Requests\StoreMaintenanceScheduleRequest;
use App\Modules\Maintenance\Resources\MaintenanceScheduleResource;
use App\Modules\Maintenance\Services\MaintenanceService;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController
{
    use ApiResponseTrait;

    public function __construct(private MaintenanceService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceSchedule::class);
        $paginator = $this->service->paginate($request->all());
        return $this->paginatedResponse(MaintenanceScheduleResource::collection($paginator)->resource);
    }

    public function store(StoreMaintenanceScheduleRequest $request): JsonResponse
    {
        $this->authorize('create', MaintenanceSchedule::class);
        $schedule = $this->service->create($request->validated());
        return $this->successResponse(new MaintenanceScheduleResource($schedule), 201, 'Planification de maintenance créée.');
    }

    public function show(string $uuid): JsonResponse
    {
        $schedule = MaintenanceSchedule::where('uuid', $uuid)->with('vehicle', 'records')->firstOrFail();
        $this->authorize('update', $schedule);
        return $this->successResponse(new MaintenanceScheduleResource($schedule));
    }

    public function complete(CompleteMaintenanceRequest $request, string $uuid): JsonResponse
    {
        $schedule = MaintenanceSchedule::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $schedule);
        $record = $this->service->completeService($schedule, $request->validated());
        return $this->successResponse($record, 200, 'Service de maintenance enregistré.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $schedule = MaintenanceSchedule::where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $schedule);
        $this->service->delete($schedule);
        return $this->successResponse(null, 200, 'Planification supprimée.');
    }

    public function alerts(): JsonResponse
    {
        $this->authorize('viewAny', MaintenanceSchedule::class);
        $overdue  = $this->service->getOverdueSchedules();
        $dueSoon = $this->service->getDueSoon();
        return $this->successResponse([
            'overdue'  => MaintenanceScheduleResource::collection($overdue),
            'due_soon' => MaintenanceScheduleResource::collection($dueSoon),
        ]);
    }

    protected function authorize(string $ability, mixed $target): void
    {
        app(Gate::class)->authorize($ability, $target);
    }
}
