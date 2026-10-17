<?php

namespace App\Modules\Incident\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Incident\Models\Incident;
use App\Modules\Incident\Requests\StoreIncidentRequest;
use App\Modules\Incident\Resources\IncidentResource;
use App\Modules\Incident\Services\IncidentService;
use App\Modules\Mission\Models\Mission;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController
{
    use ApiResponseTrait;

    public function __construct(private IncidentService $service) {}

    public function listAll(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Incident::class);

        $paginator = Incident::orderBy('created_at', 'desc')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->sync_status, fn($q) => $q->where('sync_status', $request->sync_status))
            ->when($request->description, fn($q) => $q->where('description', 'like', "%{$request->description}%"))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->paginate(min((int) $request->input('per_page', 10), 100));

        return response()->json([
            'success' => true,
            'data'    => IncidentResource::collection($paginator->items()),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'message' => 'Succès',
        ]);
    }

    public function index(string $missionUuid): JsonResponse
    {
        $mission = Mission::where('uuid', $missionUuid)->firstOrFail();
        $incidents = Incident::where('mission_id', $mission->id)->orderBy('created_at', 'desc')->paginate(min((int) request('per_page', 10), 100));
        return $this->paginatedResponse(IncidentResource::collection($incidents)->resource);
    }

    public function store(StoreIncidentRequest $request, string $missionUuid): JsonResponse
    {
        $mission = Mission::where('uuid', $missionUuid)->firstOrFail();
        $incident = $this->service->create($mission, $request->validated());
        return $this->successResponse(new IncidentResource($incident), 201, 'Incident signalé.');
    }

    public function show(string $uuid): JsonResponse
    {
        $incident = Incident::where('uuid', $uuid)->firstOrFail();
        $this->authorize('view', $incident);
        return $this->successResponse(new IncidentResource($incident));
    }

    public function destroy(string $uuid): JsonResponse
    {
        $incident = Incident::where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $incident);
        $this->service->delete($incident);
        return $this->successResponse(null, 200, 'Incident supprimé.');
    }

    protected function authorize(string $ability, mixed $target): void
    {
        app(Gate::class)->authorize($ability, $target);
    }
}
