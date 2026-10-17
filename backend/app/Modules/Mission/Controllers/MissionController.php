<?php

namespace App\Modules\Mission\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Mission\Models\Mission;
use App\Modules\Mission\Requests\StoreMissionRequest;
use App\Modules\Mission\Requests\UpdateMissionRequest;
use App\Modules\Mission\Resources\MissionResource;
use App\Modules\Mission\Services\MissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MissionController
{
    use ApiResponseTrait;

    public function __construct(private MissionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Mission::class);
        $paginator = $this->service->paginate($request->only([
            'status', 'driver_id', 'vehicle_id', 'origin', 'destination', 'from', 'to', 'per_page',
        ]));
        return $this->paginatedResponse(MissionResource::collection($paginator)->resource);
    }

    public function store(StoreMissionRequest $request): JsonResponse
    {
        $this->authorize('create', Mission::class);
        $mission = $this->service->create($request->validated());
        return $this->successResponse(new MissionResource($mission), 201, 'Mission créée.');
    }

    public function show(string $uuid): JsonResponse
    {
        $mission = Mission::where('uuid', $uuid)->firstOrFail();
        $this->authorize('view', $mission);
        return $this->successResponse(new MissionResource(
            $mission->load(['vehicle', 'driver.user', 'expenses', 'incidents'])
        ));
    }

    public function update(UpdateMissionRequest $request, string $uuid): JsonResponse
    {
        $mission = Mission::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $mission);
        $mission = $this->service->update($mission, $request->validated());
        return $this->successResponse(new MissionResource($mission), 200, 'Mission mise à jour.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $mission = Mission::where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $mission);
        $this->service->delete($mission);
        return $this->successResponse(null, 200, 'Mission supprimée.');
    }

    public function start(string $uuid): JsonResponse
    {
        $mission = Mission::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $mission);

        if ($mission->status !== 'planned') {
            return $this->errorResponse('La mission ne peut être démarrée que si elle est en attente.', 422);
        }

        return $this->successResponse(new MissionResource($this->service->start($mission)), 200, 'Mission démarrée.');
    }

    public function complete(string $uuid): JsonResponse
    {
        $mission = Mission::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $mission);

        if ($mission->status !== 'in_progress') {
            return $this->errorResponse('La mission doit être en cours pour être complétée.', 422);
        }

        return $this->successResponse(new MissionResource($this->service->complete($mission)), 200, 'Mission complétée.');
    }

    public function cancel(string $uuid): JsonResponse
    {
        $mission = Mission::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $mission);
        return $this->successResponse(new MissionResource($this->service->cancel($mission)), 200, 'Mission annulée.');
    }

    protected function authorize(string $ability, mixed $target): void
    {
        app(\Illuminate\Contracts\Auth\Access\Gate::class)->authorize($ability, $target);
    }
}
