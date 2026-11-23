<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Subscription;
use App\Modules\Subscription\Services\SubscriptionService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    public function test_changing_plan_cancels_the_previous_subscription_and_updates_quotas(): void
    {
        $previous = Subscription::create([
            'company_id' => $this->company->id,
            'plan' => 'pro',
            'start_date' => now()->subMonth(),
            'billing_status' => 'active',
        ]);
        $this->actingAsAdmin();
        $service = new SubscriptionService();

        $new = $service->changePlan('enterprise');

        $this->assertSame('cancelled', $previous->fresh()->billing_status);
        $this->assertSame('enterprise', $new->plan);
        $this->assertSame('active', $new->billing_status);
        $this->assertSame('enterprise', $this->company->fresh()->plan);
        $this->assertSame(999, $this->company->fresh()->max_users);
        $this->assertSame($new->id, $service->getCurrentSubscription()->id);
        $this->assertSame([
            'plan' => 'enterprise',
            'vehicles' => ['used' => 0, 'max' => 999],
            'drivers' => ['used' => 0, 'max' => 999],
            'users' => ['used' => 1, 'max' => 999],
        ], $service->getQuotaUsage());
    }

    public function test_rejects_downgrade_when_user_count_exceeds_target_quota(): void
    {
        $this->createUser('driver');
        $this->createUser('manager');
        $this->createUser('driver');

        try {
            (new SubscriptionService())->changePlanForCompany($this->company, 'starter');
            $this->fail('A plan with insufficient user capacity must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('4 utilisateur', $exception->errors()['plan'][0]);
        }

        $this->assertSame('pro', $this->company->fresh()->plan);
        $this->assertDatabaseMissing('subscriptions', [
            'company_id' => $this->company->id,
            'plan' => 'starter',
        ]);
    }

    public function test_change_for_company_honors_the_explicit_end_date(): void
    {
        $subscription = (new SubscriptionService())->changePlanForCompany(
            $this->company,
            'starter',
            '2030-12-31',
        );

        $this->assertSame('2030-12-31', $subscription->end_date->toDateString());
        $this->assertSame(3, $this->company->fresh()->max_users);
    }

    public function test_subscription_api_changes_plan_and_returns_quotas(): void
    {
        $this->actingAsAdmin();
        $this->getJson('/api/v1/subscriptions/current')->assertOk()->assertJsonPath('data', null);
        $this->postJson('/api/v1/subscriptions/change-plan', ['plan' => 'enterprise'])
            ->assertOk()->assertJsonPath('data.plan', 'enterprise');
        // Simulate a fresh authenticated user for the next HTTP request.
        $this->actingAs($this->admin->fresh(), 'sanctum');
        $this->getJson('/api/v1/subscriptions/current')->assertOk()->assertJsonPath('data.plan', 'enterprise');
        $this->getJson('/api/v1/subscriptions/quotas')->assertOk()->assertJsonPath('data.users.max', 999);
        $this->postJson('/api/v1/subscriptions/change-plan', ['plan' => 'invalid'])->assertUnprocessable();
    }
}
