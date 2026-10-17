<?php

namespace App\Modules\Subscription\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Company\Models\Company;
use App\Modules\Company\Models\Subscription;
use App\Modules\Subscription\Requests\ChangePlanRequest;
use App\Modules\Subscription\Resources\SubscriptionResource;
use App\Modules\Subscription\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;

class SubscriptionController
{
    use ApiResponseTrait;

    public function __construct(private SubscriptionService $service) {}

    public function current(): JsonResponse
    {
        $subscription = $this->service->getCurrentSubscription();
        if (!$subscription) {
            return $this->successResponse(null, 200, 'Aucun abonnement actif.');
        }
        return $this->successResponse(new SubscriptionResource($subscription));
    }

    public function changePlan(ChangePlanRequest $request): JsonResponse
    {
        $subscription = $this->service->changePlan($request->validated()['plan']);
        return $this->successResponse(new SubscriptionResource($subscription), 200, 'Plan mis à jour avec succès.');
    }

    public function quotas(): JsonResponse
    {
        $data = $this->service->getQuotaUsage();
        return $this->successResponse($data);
    }
}
