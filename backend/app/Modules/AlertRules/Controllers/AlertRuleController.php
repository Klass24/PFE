<?php

namespace App\Modules\AlertRules\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\AlertRules\Models\AlertRule;
use App\Modules\AlertRules\Services\AlertRuleService;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertRuleController
{
    use ApiResponseTrait;

    public function __construct(private AlertRuleService $service) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->service->paginate($request->all());
        return $this->paginatedResponse($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'trigger_type' => 'required|in:mission_completed,expense_created,incident_reported,document_expiring,fuel_anomaly,geofence_breach,maintenance_due,speed_exceeded',
            'conditions'   => 'required|array',
            'actions'      => 'required|array',
            'priority'     => 'nullable|in:low,medium,high',
        ]);

        $rule = $this->service->create($request->all());
        return $this->successResponse($rule, 201, 'Règle d\'alerte créée.');
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $rule = AlertRule::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'name'       => 'nullable|string|max:255',
            'conditions' => 'nullable|array',
            'actions'    => 'nullable|array',
            'is_active'  => 'nullable|boolean',
            'priority'   => 'nullable|in:low,medium,high',
        ]);

        $rule = $this->service->update($rule, $request->all());
        return $this->successResponse($rule, 200, 'Règle mise à jour.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $rule = AlertRule::where('uuid', $uuid)->firstOrFail();
        $this->service->delete($rule);
        return $this->successResponse(null, 200, 'Règle supprimée.');
    }
}
