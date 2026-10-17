<?php

namespace App\Modules\Admin\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Admin\Requests\AdminChangePlanRequest;
use App\Modules\Admin\Requests\AdminStoreSubscriptionRequest;
use App\Modules\Admin\Requests\AdminUpdateSubscriptionRequest;
use App\Modules\Admin\Resources\AdminSubscriptionResource;
use App\Modules\Company\Models\Company;
use App\Modules\Company\Models\Subscription;
use App\Modules\Subscription\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSubscriptionController
{
    use ApiResponseTrait;

    public function __construct(private SubscriptionService $service) {}

    /**
     * GET /admin/subscriptions — liste globale de tous les abonnements.
     */
    public function all(Request $request): JsonResponse
    {
        $paginator = Subscription::query()
            ->with('company')
            ->when($request->billing_status, fn($q, $v) => $q->where('billing_status', $v))
            ->when($request->plan, fn($q, $v) => $q->where('plan', $v))
            ->when($request->search, fn($q, $v) => $q->whereHas('company', fn($cq) => $cq->where('name', 'like', "%{$v}%")))
            ->orderByDesc('created_at')
            ->paginate(15);

        return $this->paginatedResponse(
            AdminSubscriptionResource::collection($paginator)->resource,
            'Abonnements récupérés.'
        );
    }

    /**
     * GET /admin/companies/{uuid}/subscriptions — historique des abonnements d'une entreprise.
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $company = Company::where('uuid', $uuid)->firstOrFail();

        $paginator = Subscription::where('company_id', $company->id)
            ->with('company')
            ->when($request->billing_status, fn($q, $v) => $q->where('billing_status', $v))
            ->orderByDesc('created_at')
            ->paginate(15);

        return $this->paginatedResponse(
            AdminSubscriptionResource::collection($paginator)->resource,
            'Historique des abonnements récupéré.'
        );
    }

    /**
     * GET /admin/companies/{uuid}/subscriptions/current — abonnement actif.
     */
    public function current(string $uuid): JsonResponse
    {
        $company = Company::where('uuid', $uuid)->firstOrFail();
        $subscription = $company->activeSubscription;

        if (!$subscription) {
            return $this->successResponse(null, 200, 'Aucun abonnement actif.');
        }

        $subscription->load('company');

        return $this->successResponse(new AdminSubscriptionResource($subscription));
    }

    /**
     * POST /admin/companies/{uuid}/subscriptions — créer un abonnement manuellement.
     */
    public function store(AdminStoreSubscriptionRequest $request, string $uuid): JsonResponse
    {
        $company = Company::where('uuid', $uuid)->firstOrFail();
        $data = $request->validated();

        // Si le nouvel abonnement est actif, désactiver les précédents
        if ($data['billing_status'] === 'active') {
            Subscription::where('company_id', $company->id)
                ->where('billing_status', 'active')
                ->update(['billing_status' => 'cancelled', 'end_date' => now()]);

            // Synchroniser le plan de l'entreprise
            if ($data['plan'] !== $company->plan) {
                $company->update(['plan' => $data['plan']]);
            }
        }

        $subscription = Subscription::create([
            'company_id'     => $company->id,
            'plan'           => $data['plan'],
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'] ?? null,
            'billing_status' => $data['billing_status'],
        ]);

        Log::info('Subscription created by super_admin', [
            'subscription_id' => $subscription->id,
            'company_id'      => $company->id,
            'plan'            => $data['plan'],
            'by'              => auth()->id(),
        ]);

        $subscription->load('company');

        return $this->successResponse(new AdminSubscriptionResource($subscription), 201, 'Abonnement créé.');
    }

    /**
     * POST /admin/companies/{uuid}/subscriptions/change-plan — changer le plan.
     */
    public function changePlan(AdminChangePlanRequest $request, string $uuid): JsonResponse
    {
        $company = Company::where('uuid', $uuid)->firstOrFail();
        $data = $request->validated();

        $subscription = $this->service->changePlanForCompany(
            $company,
            $data['plan'],
            $data['end_date'] ?? null
        );

        $subscription->load('company');

        return $this->successResponse(
            new AdminSubscriptionResource($subscription),
            200,
            "Plan de l'entreprise {$company->name} mis à jour vers {$data['plan']}."
        );
    }

    /**
     * PUT /admin/subscriptions/{id} — modifier un abonnement (statut, date fin).
     */
    public function update(AdminUpdateSubscriptionRequest $request, int $id): JsonResponse
    {
        $subscription = Subscription::with('company')->findOrFail($id);
        $data = $request->validated();

        $subscription->update($data);

        Log::info('Subscription updated by super_admin', [
            'subscription_id' => $subscription->id,
            'company_id'      => $subscription->company_id,
            'changes'         => $data,
            'by'              => auth()->id(),
        ]);

        return $this->successResponse(
            new AdminSubscriptionResource($subscription->fresh()->load('company')),
            200,
            'Abonnement mis à jour.'
        );
    }

    /**
     * DELETE /admin/subscriptions/{id} — supprimer un abonnement.
     */
    public function destroy(int $id): JsonResponse
    {
        $subscription = Subscription::findOrFail($id);

        Log::info('Subscription deleted by super_admin', [
            'subscription_id' => $subscription->id,
            'company_id'      => $subscription->company_id,
            'by'              => auth()->id(),
        ]);

        $subscription->delete();

        return $this->successResponse(null, 200, 'Abonnement supprimé.');
    }

    /**
     * GET /admin/companies/{uuid}/quotas — usage des quotas.
     */
    public function quotas(string $uuid): JsonResponse
    {
        $company = Company::where('uuid', $uuid)->firstOrFail();
        $data = $this->service->getQuotaUsageForCompany($company);

        return $this->successResponse($data);
    }
}
