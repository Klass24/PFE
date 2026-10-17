<?php

namespace App\Modules\Vehicle\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Vehicle\Requests\StoreVehicleRequest;
use App\Modules\Vehicle\Requests\UpdateVehicleRequest;
use App\Modules\Vehicle\Resources\VehicleResource;
use App\Modules\Vehicle\Services\VehicleService;
use App\Modules\Vehicle\Services\VehicleCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController
{
    use ApiResponseTrait;

    public function __construct(private VehicleService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);
        $paginator = $this->service->paginate($request->only([
            'status', 'brand', 'model', 'type', 'fuel_type', 'plate_number', 'search', 'from', 'to', 'per_page',
        ]));
        return $this->paginatedResponse(VehicleResource::collection($paginator)->resource, 'Véhicules récupérés.');
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $this->authorize('create', Vehicle::class);
        $vehicle = $this->service->create($request->validated());
        return $this->successResponse(new VehicleResource($vehicle), 201, 'Véhicule créé avec succès.');
    }

    public function show(string $uuid): JsonResponse
    {
        $vehicle = Vehicle::where('uuid', $uuid)->firstOrFail();
        $this->authorize('view', $vehicle);
        return $this->successResponse(new VehicleResource($vehicle->load('documents')));
    }

    public function update(UpdateVehicleRequest $request, string $uuid): JsonResponse
    {
        $vehicle = Vehicle::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $vehicle);
        $vehicle = $this->service->update($vehicle, $request->validated());
        return $this->successResponse(new VehicleResource($vehicle), 200, 'Véhicule mis à jour.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $vehicle = Vehicle::where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $vehicle);
        $this->service->delete($vehicle);
        return $this->successResponse(null, 200, 'Véhicule supprimé.');
    }

    public function uploadImage(Request $request, string $uuid): JsonResponse
    {
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048']);
        $vehicle = Vehicle::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $vehicle);
        $vehicle = $this->service->uploadImage($vehicle, $request->file('image'));
        return $this->successResponse(new VehicleResource($vehicle), 200, 'Image mise à jour.');
    }

    public function catalogBrands(): JsonResponse
    {
        return $this->successResponse(VehicleCatalog::brands());
    }

    public function catalogModels(string $brand): JsonResponse
    {
        $models = VehicleCatalog::modelsFor($brand);
        return $this->successResponse($models);
    }

    protected function authorize(string $ability, mixed $target): void
    {
        app(\Illuminate\Contracts\Auth\Access\Gate::class)->authorize($ability, $target);
    }
}
