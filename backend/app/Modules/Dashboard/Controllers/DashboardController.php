<?php

namespace App\Modules\Dashboard\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    use ApiResponseTrait;

    public function __construct(private DashboardService $service) {}

    public function stats(): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        return $this->successResponse($this->service->getStats($companyId), 200, 'Statistiques récupérées.');
    }

    public function expensesByVehicle(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $companyId = auth()->user()->company_id;
        $from      = $request->from ?? now()->startOfMonth()->toDateString();
        $to        = $request->to   ?? now()->toDateString();

        return $this->successResponse($this->service->getExpensesByVehicle($companyId, $from, $to));
    }

    public function incidentsByType(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $companyId = auth()->user()->company_id;
        $from      = $request->from ?? now()->startOfMonth()->toDateString();
        $to        = $request->to   ?? now()->toDateString();

        return $this->successResponse($this->service->getIncidentsByType($companyId, $from, $to));
    }

    public function costPerMission(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $companyId = auth()->user()->company_id;
        $from      = $request->from ?? now()->startOfMonth()->toDateString();
        $to        = $request->to   ?? now()->toDateString();

        return $this->successResponse($this->service->getCostPerMission($companyId, $from, $to));
    }

    public function expiringDocuments(): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        return $this->successResponse($this->service->getExpiringDocuments($companyId));
    }

    public function driverLeaderboard(): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        return $this->successResponse($this->service->getDriverLeaderboard($companyId));
    }
}

