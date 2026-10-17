<?php

namespace App\Modules\Payment\Services;

use App\Modules\Company\Models\Company;
use App\Modules\Company\Models\Subscription;
use App\Modules\Payment\Models\Payment;
use App\Modules\Pricing\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeService
{
    private string $secretKey;
    private string $baseUrl = 'https://api.stripe.com/v1';

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret');
    }

    /**
     * Créer ou récupérer un customer Stripe pour l'entreprise.
     */
    public function getOrCreateCustomer(Company $company): string
    {
        $subscription = $company->activeSubscription;

        // Si déjà un customer ID, le retourner
        if ($subscription?->gateway_customer_id) {
            return $subscription->gateway_customer_id;
        }

        $response = Http::withToken($this->secretKey, 'Bearer')
            ->asForm()
            ->post("{$this->baseUrl}/customers", [
                'email'    => $company->email,
                'name'     => $company->name,
                'metadata' => [
                    'company_uuid' => $company->uuid,
                    'company_id'   => $company->id,
                ],
            ]);

        if (!$response->successful()) {
            Log::error('Stripe customer creation failed', ['response' => $response->json()]);
            throw new \RuntimeException('Impossible de créer le client Stripe.');
        }

        return $response->json('id');
    }

    /**
     * Créer une session Checkout Stripe pour un changement de plan.
     */
    public function createCheckoutSession(Company $company, Plan $plan, string $billingCycle = 'monthly'): array
    {
        $customerId = $this->getOrCreateCustomer($company);
        $price      = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $interval   = $billingCycle === 'yearly' ? 'year' : 'month';

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        $response = Http::withToken($this->secretKey, 'Bearer')
            ->asForm()
            ->post("{$this->baseUrl}/checkout/sessions", [
                'customer'                   => $customerId,
                'mode'                       => 'subscription',
                'payment_method_types'       => ['card'],
                'line_items[0][price_data][currency]'     => 'eur',
                'line_items[0][price_data][unit_amount]'  => (int) ($price * 100),
                'line_items[0][price_data][recurring][interval]' => $interval,
                'line_items[0][price_data][product_data][name]'  => "FleetControl — {$plan->name}",
                'line_items[0][quantity]'    => 1,
                'success_url'               => "{$frontendUrl}/pricing?payment=success&session_id={CHECKOUT_SESSION_ID}",
                'cancel_url'                => "{$frontendUrl}/pricing?payment=cancelled",
                'metadata[company_uuid]'    => $company->uuid,
                'metadata[plan_slug]'       => $plan->slug,
                'metadata[billing_cycle]'   => $billingCycle,
            ]);

        if (!$response->successful()) {
            Log::error('Stripe checkout session failed', ['response' => $response->json()]);
            throw new \RuntimeException('Impossible de créer la session de paiement Stripe.');
        }

        $session = $response->json();

        Log::info('Stripe checkout session created', [
            'session_id' => $session['id'],
            'company_id' => $company->id,
            'plan'       => $plan->slug,
        ]);

        return [
            'checkout_url' => $session['url'],
            'session_id'   => $session['id'],
        ];
    }

    /**
     * Traiter un webhook Stripe (checkout.session.completed).
     */
    public function handleCheckoutCompleted(array $session): void
    {
        $metadata     = $session['metadata'] ?? [];
        $companyUuid  = $metadata['company_uuid'] ?? null;
        $planSlug     = $metadata['plan_slug'] ?? null;
        $billingCycle = $metadata['billing_cycle'] ?? 'monthly';

        if (!$companyUuid || !$planSlug) {
            Log::warning('Stripe webhook: metadata manquant', $metadata);
            return;
        }

        $company = Company::where('uuid', $companyUuid)->first();
        $plan    = Plan::where('slug', $planSlug)->first();

        if (!$company || !$plan) {
            Log::warning('Stripe webhook: company ou plan introuvable', $metadata);
            return;
        }

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
            'payment_gateway'         => 'stripe',
            'gateway_subscription_id' => $session['subscription'] ?? null,
            'gateway_customer_id'     => $session['customer'] ?? null,
            'amount'                  => ($session['amount_total'] ?? 0) / 100,
            'currency'                => $session['currency'] ?? 'eur',
            'billing_cycle'           => $billingCycle,
            'next_billing_at'         => $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth(),
            'gateway_metadata'        => $session,
        ]);

        // Enregistrer le paiement
        Payment::create([
            'company_id'         => $company->id,
            'subscription_id'    => $subscription->id,
            'gateway'            => 'stripe',
            'gateway_payment_id' => $session['payment_intent'] ?? $session['id'],
            'amount'             => ($session['amount_total'] ?? 0) / 100,
            'currency'           => $session['currency'] ?? 'eur',
            'status'             => 'succeeded',
            'payment_method'     => 'card',
            'description'        => "Abonnement {$plan->name} ({$billingCycle})",
            'paid_at'            => now(),
            'gateway_metadata'   => $session,
        ]);

        Log::info('Stripe payment processed', [
            'company_id'      => $company->id,
            'plan'            => $plan->slug,
            'subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Annuler un abonnement Stripe.
     */
    public function cancelSubscription(string $gatewaySubscriptionId): void
    {
        $response = Http::withToken($this->secretKey, 'Bearer')
            ->delete("{$this->baseUrl}/subscriptions/{$gatewaySubscriptionId}");

        if (!$response->successful()) {
            Log::error('Stripe subscription cancel failed', ['response' => $response->json()]);
        }
    }

    /**
     * Vérifier la signature du webhook.
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader): array
    {
        $secret = config('services.stripe.webhook_secret');

        $elements  = collect(explode(',', $sigHeader))->mapWithKeys(function ($part) {
            [$key, $val] = explode('=', $part, 2);
            return [$key => $val];
        });

        $timestamp = $elements->get('t');
        $signature = $elements->get('v1');

        $expectedSig = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        if (!hash_equals($expectedSig, $signature)) {
            throw new \RuntimeException('Signature Stripe invalide.');
        }

        return json_decode($payload, true);
    }
}
