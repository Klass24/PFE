<?php

namespace App\Modules\Pricing\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Pricing\Models\Plan;
use App\Modules\Pricing\Requests\StorePlanRequest;
use App\Modules\Pricing\Requests\UpdatePlanRequest;
use App\Modules\Pricing\Resources\PlanResource;
use App\Modules\Pricing\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController
{
    use ApiResponseTrait;

    public function __construct(private PricingService $service) {}

    // ─── Public (no auth needed) ──────────────────────────────────────────────

    /**
     * GET /api/v1/plans — Liste publique des plans actifs.
     */
    public function publicIndex(): JsonResponse
    {
        $plans = $this->service->getPublicPlans();
        return $this->successResponse(PlanResource::collection($plans));
    }

    /**
     * GET /api/v1/plans/{slug} — Détail public d'un plan.
     */
    public function publicShow(string $slug): JsonResponse
    {
        $plan = $this->service->getBySlug($slug);
        return $this->successResponse(new PlanResource($plan));
    }

    // ─── Authenticated (tenant) ───────────────────────────────────────────────

    /**
     * GET /api/v1/pricing/current — Plan actuel + usage quotas.
     */
    public function current(): JsonResponse
    {
        $data = $this->service->getCurrentPlanDetails();
        return $this->successResponse([
            'plan'    => $data['plan'] ? new PlanResource($data['plan']) : null,
            'usage'   => $data['usage'],
            'company' => $data['company'],
        ]);
    }

    /**
     * GET /api/v1/pricing/compare/{slug} — Comparer plan actuel vs plan cible.
     */
    public function compare(string $slug): JsonResponse
    {
        $company = auth()->user()->company;
        $data    = $this->service->comparePlans($company, $slug);

        return $this->successResponse([
            'current_plan' => $data['current_plan'] ? new PlanResource($data['current_plan']) : null,
            'target_plan'  => new PlanResource($data['target_plan']),
            'usage'        => $data['usage'],
            'can_switch'   => $data['can_switch'],
            'is_upgrade'   => $data['is_upgrade'],
            'price_diff'   => $data['price_diff'],
        ]);
    }

    // ─── Super Admin CRUD ─────────────────────────────────────────────────────

    /**
     * GET /api/v1/admin/plans — Tous les plans (admin).
     */
    public function index(): JsonResponse
    {
        $plans = $this->service->getAllPlans();
        return $this->successResponse(PlanResource::collection($plans));
    }

    /**
     * GET /api/v1/admin/plans/{uuid} — Détail plan (admin).
     */
    public function show(string $uuid): JsonResponse
    {
        $plan = $this->service->getByUuid($uuid);
        return $this->successResponse(new PlanResource($plan));
    }

    /**
     * POST /api/v1/admin/plans — Créer un plan.
     */
    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = $this->service->create($request->validated());
        return $this->successResponse(new PlanResource($plan), 201, 'Plan créé avec succès.');
    }

    /**
     * PUT /api/v1/admin/plans/{uuid} — Mettre à jour un plan.
     */
    public function update(UpdatePlanRequest $request, string $uuid): JsonResponse
    {
        $plan = $this->service->getByUuid($uuid);
        $plan = $this->service->update($plan, $request->validated());
        return $this->successResponse(new PlanResource($plan), 200, 'Plan mis à jour.');
    }

    /**
     * DELETE /api/v1/admin/plans/{uuid} — Supprimer un plan.
     */
    public function destroy(string $uuid): JsonResponse
    {
        $plan = $this->service->getByUuid($uuid);
        $this->service->delete($plan);
        return $this->successResponse(null, 200, 'Plan supprimé.');
    }
}
