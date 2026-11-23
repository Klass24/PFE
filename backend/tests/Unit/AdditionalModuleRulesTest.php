<?php

namespace Tests\Unit;

use App\Modules\AlertRules\Models\AlertRule;
use App\Modules\ApiToken\Models\ApiToken;
use App\Modules\Company\Models\Subscription;
use App\Modules\Export\Services\ImportService;
use App\Modules\Payment\Services\PayPalService;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;
use Mockery;

class AdditionalModuleRulesTest extends \Illuminate\Foundation\Testing\TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function test_alert_rule_supports_comparison_and_membership_operators(): void
    {
        $rule = new AlertRule([
            'conditions' => [
                'amount' => ['operator' => '>', 'value' => 10],
                'status' => ['operator' => '!=', 'value' => 'cancelled'],
                'type' => ['operator' => 'in', 'value' => ['fuel', 'repair']],
            ],
        ]);

        $this->assertTrue($rule->evaluate([
            'amount' => 11,
            'status' => 'approved',
            'type' => 'fuel',
        ]));
        $this->assertFalse($rule->evaluate([
            'amount' => 10,
            'status' => 'approved',
            'type' => 'fuel',
        ]));
    }

    public function test_alert_rule_rejects_missing_values_and_unsupported_operators(): void
    {
        $rule = new AlertRule([
            'conditions' => ['amount' => ['operator' => 'contains', 'value' => 10]],
        ]);

        $this->assertFalse($rule->evaluate(['amount' => 10]));
        $this->assertFalse($rule->evaluate([]));
    }

    public function test_alert_rule_without_conditions_matches_any_trigger(): void
    {
        $this->assertTrue((new AlertRule(['conditions' => []]))->evaluate([]));
    }

    public function test_subscription_payment_helpers_reflect_gateway_and_status(): void
    {
        $subscription = new Subscription([
            'billing_status' => 'active',
            'payment_gateway' => 'stripe',
        ]);

        $this->assertTrue($subscription->isActive());
        $this->assertTrue($subscription->isPaid());
        $this->assertFalse($subscription->isManual());

        $subscription->billing_status = 'cancelled';
        $subscription->payment_gateway = null;

        $this->assertFalse($subscription->isActive());
        $this->assertFalse($subscription->isPaid());
        $this->assertTrue($subscription->isManual());
    }

    public function test_api_token_expiry_is_detected_from_its_expiration_date(): void
    {
        $expiredToken = (new ApiToken())->setDateFormat('Y-m-d H:i:s');
        $expiredToken->expires_at = Carbon::now()->subDay();
        $activeToken = (new ApiToken())->setDateFormat('Y-m-d H:i:s');
        $activeToken->expires_at = Carbon::now()->addDay();
        $nonExpiringToken = (new ApiToken())->setDateFormat('Y-m-d H:i:s');

        $this->assertTrue($expiredToken->isExpired());
        $this->assertFalse($activeToken->isExpired());
        $this->assertFalse($nonExpiringToken->isExpired());
    }

    public function test_import_rejects_a_csv_missing_required_vehicle_columns(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'pfe-import-');
        if ($filePath === false) {
            $this->fail('Unable to create a temporary CSV file.');
        }

        try {
            file_put_contents($filePath, "plaque;marque\nAB-123;Toyota\n");

            try {
                (new ImportService())->importVehicles($filePath, 'csv');
                $this->fail('CSV files missing required columns must be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('file', $exception->errors());
                $this->assertStringContainsString('model', $exception->errors()['file'][0]);
                $this->assertStringContainsString('year', $exception->errors()['file'][0]);
            }
        } finally {
            unlink($filePath);
        }
    }

    public function test_payment_service_processes_completed_paypal_orders_only(): void
    {
        $completedOrder = ['status' => 'COMPLETED', 'id' => 'order-1'];
        $paypal = Mockery::mock(PayPalService::class);
        $paypal->shouldReceive('captureOrder')->once()->with('order-1')->andReturn($completedOrder);
        $paypal->shouldReceive('handleOrderCaptured')->once()->with($completedOrder);

        $service = new PaymentService(
            Mockery::mock(StripeService::class),
            $paypal,
        );

        $this->assertSame($completedOrder, $service->capturePayPalOrder('order-1'));

        $pendingOrder = ['status' => 'PENDING', 'id' => 'order-2'];
        $pendingPaypal = Mockery::mock(PayPalService::class);
        $pendingPaypal->shouldReceive('captureOrder')->once()->with('order-2')->andReturn($pendingOrder);
        $pendingPaypal->shouldNotReceive('handleOrderCaptured');

        $pendingService = new PaymentService(
            Mockery::mock(StripeService::class),
            $pendingPaypal,
        );

        $this->assertSame($pendingOrder, $pendingService->capturePayPalOrder('order-2'));
    }
}
