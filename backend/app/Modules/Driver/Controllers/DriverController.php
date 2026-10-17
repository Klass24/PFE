<?php

namespace App\Modules\Driver\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Requests\StoreDriverRequest;
use App\Modules\Driver\Requests\UpdateDriverRequest;
use App\Modules\Driver\Resources\DriverResource;
use App\Modules\Driver\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController
{
    use ApiResponseTrait;

    public function __construct(private DriverService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Driver::class);
        $paginator = $this->service->paginate($request->only([
            'status', 'name', 'email', 'license_number', 'phone', 'search', 'from', 'to', 'per_page',
        ]));
        return $this->paginatedResponse(DriverResource::collection($paginator)->resource);
    }

    public function store(StoreDriverRequest $request): JsonResponse
    {
        $this->authorize('create', Driver::class);
        $driver = $this->service->create($request->validated());
        return $this->successResponse(new DriverResource($driver), 201, 'Chauffeur créé.');
    }

    public function show(string $uuid): JsonResponse
    {
        $driver = Driver::where('uuid', $uuid)->firstOrFail();
        $this->authorize('view', $driver);
        return $this->successResponse(new DriverResource($driver->load('user', 'documents')));
    }

    public function update(UpdateDriverRequest $request, string $uuid): JsonResponse
    {
        $driver = Driver::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $driver);
        $driver = $this->service->update($driver, $request->validated());
        return $this->successResponse(new DriverResource($driver), 200, 'Chauffeur mis à jour.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $driver = Driver::where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $driver);
        $this->service->delete($driver);
        return $this->successResponse(null, 200, 'Chauffeur supprimé.');
    }

    protected function authorize(string $ability, mixed $target): void
    {
        app(\Illuminate\Contracts\Auth\Access\Gate::class)->authorize($ability, $target);
    }
}
