<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Subscription;
use App\Modules\Payment\Services\PayPalService;
use App\Modules\Payment\Services\StripeService;
use App\Modules\Payment\Models\Payment;
use App\Modules\Pricing\Models\Plan;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.stripe.secret' => 'test-stripe-key',
            'services.stripe.webhook_secret' => 'test-webhook-secret',
            'services.paypal.client_id' => 'test-client-id',
            'services.paypal.client_secret' => 'test-client-secret',
            'services.paypal.mode' => 'sandbox',
        ]);
        Http::preventStrayRequests();
        $this->plan = Plan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price_monthly' => 25,
            'price_yearly' => 240,
        ]);
    }

    public function test_stripe_checkout_creates_customer_and_sends_yearly_price(): void
    {
        Http::fake([
            'api.stripe.com/v1/customers' => Http::response(['id' => 'cus_test']),
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test', 'url' => 'https://example.test/checkout']),
        ]);

        $result = (new StripeService())->createCheckoutSession($this->company, $this->plan, 'yearly');

        $this->assertSame(['checkout_url' => 'https://example.test/checkout', 'session_id' => 'cs_test'], $result);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/checkout/sessions')
            && $request['customer'] === 'cus_test'
            && $request['line_items[0][price_data][unit_amount]'] === 24000
            && $request['line_items[0][price_data][recurring][interval]'] === 'year');
    }

    public function test_stripe_customer_failure_is_reported(): void
    {
        Http::fake(['api.stripe.com/v1/customers' => Http::response(['error' => 'unavailable'], 500)]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Impossible de créer le client Stripe.');
        (new StripeService())->getOrCreateCustomer($this->company);
    }

    public function test_stripe_webhook_signature_is_verified(): void
    {
        $service = new StripeService();
        $payload = '{"type":"checkout.session.completed"}';
        $signature = hash_hmac('sha256', '123.' . $payload, 'test-webhook-secret');
        $this->assertSame(['type' => 'checkout.session.completed'], $service->verifyWebhookSignature($payload, 't=123,v1=' . $signature));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Signature Stripe invalide.');
        $service->verifyWebhookSignature($payload, 't=123,v1=invalid');
    }

    public function test_completed_stripe_checkout_updates_subscription_and_records_payment(): void
    {
        $previous = Subscription::create([
            'company_id' => $this->company->id,
            'plan' => 'pro',
            'start_date' => now(),
            'billing_status' => 'active',
        ]);
        (new StripeService())->handleCheckoutCompleted([
            'id' => 'cs_test',
            'metadata' => ['company_uuid' => (string) $this->company->uuid, 'plan_slug' => 'enterprise', 'billing_cycle' => 'yearly'],
            'subscription' => 'sub_test',
            'customer' => 'cus_test',
            'payment_intent' => 'pi_test',
            'amount_total' => 24000,
            'currency' => 'eur',
        ]);

        $this->assertSame('cancelled', $previous->fresh()->billing_status);
        $this->assertSame('enterprise', $this->company->fresh()->plan);
        $this->assertDatabaseHas('subscriptions', ['company_id' => $this->company->id, 'gateway_subscription_id' => 'sub_test', 'billing_status' => 'active']);
        $this->assertDatabaseHas('payments', ['company_id' => $this->company->id, 'gateway_payment_id' => 'pi_test', 'amount' => 240, 'status' => 'succeeded']);
    }

    public function test_paypal_order_and_capture_use_sandbox_and_requested_price(): void
    {
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            'api-m.sandbox.paypal.com/v2/checkout/orders/order-test/capture' => Http::response(['id' => 'order-test', 'status' => 'COMPLETED']),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response(['id' => 'order-test', 'links' => [['rel' => 'approve', 'href' => 'https://example.test/approve']]]),
        ]);
        $service = new PayPalService();

        $this->assertSame(['order_id' => 'order-test', 'approval_url' => 'https://example.test/approve'], $service->createOrder($this->company, $this->plan, 'yearly'));
        $this->assertSame('COMPLETED', $service->captureOrder('order-test')['status']);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v2/checkout/orders')
            && $request['purchase_units'][0]['amount']['value'] === '240.00');
    }

    public function test_paypal_authentication_failure_is_reported(): void
    {
        Http::fake(['api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([], 401)]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Impossible d\'obtenir le token PayPal.');
        (new PayPalService())->captureOrder('order-test');
    }

    public function test_completed_paypal_capture_updates_subscription_and_records_payment(): void
    {
        (new PayPalService())->handleOrderCaptured([
            'id' => 'order-test',
            'purchase_units' => [[
                'custom_id' => json_encode(['company_uuid' => (string) $this->company->uuid, 'plan_slug' => 'enterprise']),
                'payments' => ['captures' => [['id' => 'capture-test', 'amount' => ['value' => '25.00', 'currency_code' => 'EUR']]]],
            ]],
        ]);

        $this->assertSame('enterprise', $this->company->fresh()->plan);
        $this->assertDatabaseHas('subscriptions', ['company_id' => $this->company->id, 'payment_gateway' => 'paypal', 'billing_status' => 'active']);
        $this->assertDatabaseHas('payments', ['company_id' => $this->company->id, 'gateway_payment_id' => 'capture-test', 'amount' => 25, 'status' => 'succeeded']);
    }

    public function test_payment_api_checkout_capture_and_history(): void
    {
        Http::fake([
            'api.stripe.com/v1/customers' => Http::response(['id' => 'cus_test']),
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test', 'url' => 'https://example.test/checkout']),
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            'api-m.sandbox.paypal.com/v2/checkout/orders/order-test/capture' => Http::response(['id' => 'order-test', 'status' => 'PENDING']),
        ]);
        $this->actingAsAdmin();
        $this->postJson('/api/v1/payments/checkout', ['plan_slug' => 'enterprise', 'gateway' => 'stripe'])
            ->assertOk()->assertJsonPath('data.session_id', 'cs_test');
        $this->postJson('/api/v1/payments/paypal/capture', ['order_id' => 'order-test'])
            ->assertOk()->assertJsonPath('data.status', 'PENDING');
        $this->getJson('/api/v1/payments/history')->assertOk();
        $payment = Payment::create([
            'company_id' => $this->company->id,
            'gateway' => 'stripe',
            'gateway_payment_id' => 'payment-test',
            'amount' => 25,
            'currency' => 'eur',
            'status' => 'succeeded',
        ]);
        $this->actingAs($this->createUser('super_admin'), 'sanctum');
        $this->getJson('/api/v1/admin/payments?gateway=stripe&status=succeeded')->assertOk();
        $this->getJson('/api/v1/admin/payments/' . $payment->uuid)->assertOk()->assertJsonPath('data.uuid', (string) $payment->uuid);
    }

    public function test_webhooks_process_valid_stripe_events_and_reject_invalid_signatures(): void
    {
        $payload = json_encode(['type' => 'unhandled', 'data' => ['object' => []]]);
        $signature = hash_hmac('sha256', '123.' . $payload, 'test-webhook-secret');
        $this->call('POST', '/api/v1/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=123,v1=' . $signature,
        ], $payload)->assertOk()->assertJsonPath('received', true);
        $this->call('POST', '/api/v1/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't=123,v1=invalid',
        ], $payload)->assertStatus(400);
        $this->postJson('/api/v1/webhooks/paypal', ['event_type' => 'PAYMENT.CAPTURE.COMPLETED', 'resource' => []])
            ->assertOk()->assertJsonPath('received', true);
    }
}
