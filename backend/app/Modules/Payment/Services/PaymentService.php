<?php

namespace App\Modules\Payment\Services;

use App\Modules\Company\Models\Company;
use App\Modules\Payment\Models\Payment;
use App\Modules\Pricing\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private StripeService $stripe,
        private PayPalService $paypal,
    ) {}

    /**
     * Initier un checkout selon la gateway choisie.
     */
    public function initiateCheckout(Company $company, Plan $plan, string $gateway, string $billingCycle = 'monthly'): array
    {
        return match ($gateway) {
            'stripe' => $this->stripe->createCheckoutSession($company, $plan, $billingCycle),
            'paypal' => $this->paypal->createOrder($company, $plan, $billingCycle),
            default  => throw new \InvalidArgumentException("Gateway non supportée : {$gateway}"),
        };
    }

    /**
     * Capturer un paiement PayPal (après retour utilisateur).
     */
    public function capturePayPalOrder(string $orderId): array
    {
        $order = $this->paypal->captureOrder($orderId);

        if (($order['status'] ?? '') === 'COMPLETED') {
            $this->paypal->handleOrderCaptured($order);
        }

        return $order;
    }

    /**
     * Historique des paiements pour une entreprise.
     */
    public function getPaymentHistory(int $companyId): LengthAwarePaginator
    {
        return Payment::where('company_id', $companyId)
            ->with('subscription')
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    /**
     * Historique global des paiements (super_admin).
     */
    public function getAllPayments(array $filters = []): LengthAwarePaginator
    {
        return Payment::query()
            ->with(['company', 'subscription'])
            ->when($filters['gateway'] ?? null, fn ($q, $v) => $q->where('gateway', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->whereHas('company', fn ($cq) => $cq->where('name', 'like', "%{$v}%")))
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    /**
     * Détail d'un paiement par UUID.
     */
    public function getByUuid(string $uuid): Payment
    {
        return Payment::where('uuid', $uuid)->with(['company', 'subscription'])->firstOrFail();
    }

    /**
     * Services accesseurs.
     */
    public function stripe(): StripeService
    {
        return $this->stripe;
    }

    public function paypal(): PayPalService
    {
        return $this->paypal;
    }
}
