<?php

namespace App\Modules\Payment\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Payment\Requests\InitiateCheckoutRequest;
use App\Modules\Payment\Resources\PaymentResource;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Pricing\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController
{
    use ApiResponseTrait;

    public function __construct(private PaymentService $service) {}

    // ─── Tenant (entreprise admin) ────────────────────────────────────────────

    /**
     * POST /api/v1/payments/checkout — Initier un checkout Stripe ou PayPal.
     */
    public function checkout(InitiateCheckoutRequest $request): JsonResponse
    {
        $data    = $request->validated();
        $company = auth()->user()->company;
        $plan    = Plan::where('slug', $data['plan_slug'])->firstOrFail();

        // Pas de paiement pour les plans gratuits
        if ($plan->isFree()) {
            return $this->errorResponse('Les plans gratuits ne nécessitent pas de paiement.', 422);
        }

        $result = $this->service->initiateCheckout(
            $company,
            $plan,
            $data['gateway'],
            $data['billing_cycle'] ?? 'monthly'
        );

        return $this->successResponse($result, 200, 'Session de paiement créée.');
    }

    /**
     * POST /api/v1/payments/paypal/capture — Capturer le paiement PayPal.
     */
    public function capturePayPal(Request $request): JsonResponse
    {
        $request->validate(['order_id' => 'required|string']);

        $order = $this->service->capturePayPalOrder($request->input('order_id'));

        return $this->successResponse([
            'status' => $order['status'] ?? 'UNKNOWN',
        ], 200, 'Paiement PayPal capturé.');
    }

    /**
     * GET /api/v1/payments/history — Historique des paiements de l'entreprise.
     */
    public function history(): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $payments  = $this->service->getPaymentHistory($companyId);

        return $this->paginatedResponse(
            PaymentResource::collection($payments)->resource,
            'Historique des paiements.'
        );
    }

    // ─── Super Admin ──────────────────────────────────────────────────────────

    /**
     * GET /api/v1/admin/payments — Tous les paiements (super_admin).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $payments = $this->service->getAllPayments($request->only(['gateway', 'status', 'search']));

        return $this->paginatedResponse(
            PaymentResource::collection($payments)->resource,
            'Paiements récupérés.'
        );
    }

    /**
     * GET /api/v1/admin/payments/{uuid} — Détail d'un paiement (super_admin).
     */
    public function adminShow(string $uuid): JsonResponse
    {
        $payment = $this->service->getByUuid($uuid);

        return $this->successResponse(new PaymentResource($payment));
    }
}
