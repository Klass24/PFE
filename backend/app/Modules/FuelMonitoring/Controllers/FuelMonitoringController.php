<?php

namespace App\Modules\FuelMonitoring\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\FuelMonitoring\Models\FuelAnomaly;
use App\Modules\FuelMonitoring\Models\FuelEntry;
use App\Modules\FuelMonitoring\Requests\StoreFuelEntryRequest;
use App\Modules\FuelMonitoring\Resources\FuelEntryResource;
use App\Modules\FuelMonitoring\Services\FuelMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FuelMonitoringController
{
    use ApiResponseTrait;

    public function __construct(private FuelMonitoringService $service) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->paginate($request->all());
        return $this->paginatedResponse(FuelEntryResource::collection($paginator)->resource);
    }

    public function store(StoreFuelEntryRequest $request): JsonResponse
    {
        $entry = $this->service->recordConsumption($request->validated());
        return $this->successResponse(new FuelEntryResource($entry), 201, 'Consommation enregistrée.');
    }

    public function anomalies(Request $request): JsonResponse
    {
        $paginator = $this->service->getAnomalies($request->all());
        return $this->paginatedResponse($paginator);
    }

    public function resolveAnomaly(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:confirmed,false_positive',
            'notes'  => 'nullable|string|max:1000',
        ]);

        $anomaly = FuelAnomaly::where('uuid', $uuid)->firstOrFail();
        $anomaly = $this->service->resolveAnomaly($anomaly, $request->status, $request->notes);
        return $this->successResponse($anomaly, 200, 'Anomalie résolue.');
    }

    public function analytics(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $companyId = auth()->user()->company_id;
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to   = $request->to ?? now()->toDateString();

        return $this->successResponse($this->service->getAnalytics($companyId, $from, $to));
    }
}
