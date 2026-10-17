<?php

namespace App\Modules\Payment\Services;

use App\Modules\Company\Models\Company;
use App\Modules\Company\Models\Subscription;
use App\Modules\Payment\Models\Payment;
use App\Modules\Pricing\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId     = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
        $this->baseUrl      = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Obtenir un access token PayPal.
     */
    private function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            Log::error('PayPal auth failed', ['response' => $response->json()]);
            throw new \RuntimeException('Impossible d\'obtenir le token PayPal.');
        }

        return $response->json('access_token');
    }

    /**
     * Créer une commande PayPal (paiement one-time pour l'abonnement).
     */
    public function createOrder(Company $company, Plan $plan, string $billingCycle = 'monthly'): array
    {
        $token       = $this->getAccessToken();
        $price       = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent'         => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $company->uuid,
                        'description'  => "FleetControl — {$plan->name} ({$billingCycle})",
                        'amount'       => [
                            'currency_code' => 'EUR',
                            'value'         => number_format($price, 2, '.', ''),
                        ],
                        'custom_id' => json_encode([
                            'company_uuid' => $company->uuid,
                            'plan_slug'    => $plan->slug,
                            'billing_cycle'=> $billingCycle,
                        ]),
                    ],
                ],
                'application_context' => [
                    'return_url' => "{$frontendUrl}/pricing?payment=success&gateway=paypal",
                    'cancel_url' => "{$frontendUrl}/pricing?payment=cancelled&gateway=paypal",
                    'brand_name' => 'FleetControl',
                    'user_action'=> 'PAY_NOW',
                ],
            ]);

        if (!$response->successful()) {
            Log::error('PayPal order creation failed', ['response' => $response->json()]);
            throw new \RuntimeException('Impossible de créer la commande PayPal.');
        }

        $order = $response->json();
        $approvalUrl = collect($order['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        Log::info('PayPal order created', [
            'order_id'   => $order['id'],
            'company_id' => $company->id,
            'plan'       => $plan->slug,
        ]);

        return [
            'order_id'     => $order['id'],
            'approval_url' => $approvalUrl,
        ];
    }

    /**
     * Capturer un paiement PayPal après approbation.
     */
    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

        if (!$response->successful()) {
            Log::error('PayPal capture failed', ['order_id' => $orderId, 'response' => $response->json()]);
            throw new \RuntimeException('Impossible de capturer le paiement PayPal.');
        }

        return $response->json();
    }

    /**
     * Traiter la capture réussie d'un paiement PayPal.
     */
    public function handleOrderCaptured(array $order): void
    {
        $purchaseUnit = $order['purchase_units'][0] ?? [];
        $customId     = json_decode($purchaseUnit['custom_id'] ?? '{}', true);
        $companyUuid  = $customId['company_uuid'] ?? null;
        $planSlug     = $customId['plan_slug'] ?? null;
        $billingCycle = $customId['billing_cycle'] ?? 'monthly';

        if (!$companyUuid || !$planSlug) {
            Log::warning('PayPal capture: metadata manquant', $customId);
            return;
        }

        $company = Company::where('uuid', $companyUuid)->first();
        $plan    = Plan::where('slug', $planSlug)->first();

        if (!$company || !$plan) {
            Log::warning('PayPal capture: company ou plan introuvable', $customId);
            return;
        }

        $capture     = $purchaseUnit['payments']['captures'][0] ?? [];
        $amountValue = (float) ($capture['amount']['value'] ?? 0);

        // Annuler abonnement actif
        Subscription::where('company_id', $company->id)
            ->where('billing_status', 'active')
            ->update(['billing_status' => 'cancelled', 'end_date' => now()]);

        // Mettre à jour le plan de l'entreprise
        $company->update(['plan' => $plan->slug, 'plan_id' => $plan->id]);

        // Créer le nouvel abonnement
        $subscription = Subscription::create([
            'company_id'              => $company->id,
            'plan'                    => $plan->slug,
            'start_date'              => now(),
            'end_date'                => $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth(),
            'billing_status'          => 'active',
            'payment_gateway'         => 'paypal',
            'gateway_subscription_id' => $order['id'],
            'gateway_customer_id'     => $order['payer']['payer_id'] ?? null,
            'amount'                  => $amountValue,
            'currency'                => $capture['amount']['currency_code'] ?? 'EUR',
            'billing_cycle'           => $billingCycle,
            'next_billing_at'         => $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth(),
            'gateway_metadata'        => $order,
        ]);

        // Enregistrer le paiement
        Payment::create([
            'company_id'         => $company->id,
            'subscription_id'    => $subscription->id,
            'gateway'            => 'paypal',
            'gateway_payment_id' => $capture['id'] ?? $order['id'],
            'amount'             => $amountValue,
            'currency'           => $capture['amount']['currency_code'] ?? 'EUR',
            'status'             => 'succeeded',
            'payment_method'     => 'paypal',
            'description'        => "Abonnement {$plan->name} ({$billingCycle})",
            'paid_at'            => now(),
            'gateway_metadata'   => $order,
        ]);

        Log::info('PayPal payment processed', [
            'company_id'      => $company->id,
            'plan'            => $plan->slug,
            'subscription_id' => $subscription->id,
        ]);
    }
}
