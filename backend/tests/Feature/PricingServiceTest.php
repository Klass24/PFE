<?php

namespace Tests\Feature;

use App\Modules\Pricing\Models\Plan;
use App\Modules\Pricing\Repositories\PlanRepository;
use App\Modules\Pricing\Services\PricingService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    private function service(): PricingService
    {
        return new PricingService(new PlanRepository());
    }

    private function plan(string $slug, array $attributes = []): Plan
    {
        return Plan::create(array_merge([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'price_monthly' => 10,
            'price_yearly' => 100,
            'max_vehicles' => 5,
            'max_drivers' => 5,
            'max_users' => 5,
            'sort_order' => 1,
            'is_active' => true,
        ], $attributes));
    }

    public function test_lists_active_plans_and_retrieves_plan_by_uuid_and_slug(): void
    {
        $inactive = $this->plan('inactive', ['is_active' => false, 'sort_order' => 0]);
        $active = $this->plan('active', ['sort_order' => 1]);
        $service = $this->service();

        $this->assertSame([$active->id], $service->getPublicPlans()->modelKeys());
        $this->assertSame([$inactive->id, $active->id], $service->getAllPlans()->modelKeys());
        $this->assertSame($active->id, $service->getByUuid($active->uuid)->id);
        $this->assertSame($active->id, $service->getBySlug('active')->id);
    }

    public function test_creates_updates_and_deletes_unused_plan(): void
    {
        $service = $this->service();
        $plan = $service->create(['name' => 'Basic', 'slug' => 'basic']);
        $updated = $service->update($plan, ['name' => 'Updated']);

        $this->assertSame('Updated', $updated->name);
        $service->delete($updated);
        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_rejects_deletion_of_plan_used_by_a_company(): void
    {
        $plan = $this->plan('used');
        $this->company->update(['plan_id' => $plan->id]);

        try {
            $this->service()->delete($plan);
            $this->fail('A plan used by a company must not be deleted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('plan', $exception->errors());
        }

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    public function test_compares_plan_quotas_and_prices(): void
    {
        $current = $this->plan('pro', ['price_monthly' => 10, 'price_yearly' => 100]);
        $target = $this->plan('premium', [
            'price_monthly' => 25,
            'price_yearly' => 240,
            'sort_order' => 2,
            'max_users' => 1,
        ]);
        $this->createUser('driver');

        $result = $this->service()->comparePlans($this->company, 'premium');

        $this->assertSame($current->id, $result['current_plan']->id);
        $this->assertSame($target->id, $result['target_plan']->id);
        $this->assertSame(2, $result['usage']['users']['used']);
        $this->assertFalse($result['can_switch']);
        $this->assertTrue($result['is_upgrade']);
        $this->assertEquals(['monthly' => 15, 'yearly' => 140], $result['price_diff']);
    }

    public function test_rejects_comparison_to_unknown_plan(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Plan cible introuvable.');

        $this->service()->comparePlans($this->company, 'unknown');
    }

    public function test_gets_current_plan_and_company_usage(): void
    {
        $plan = $this->plan('pro');
        $this->actingAsAdmin();

        $result = $this->service()->getCurrentPlanDetails();

        $this->assertSame($plan->id, $result['plan']->id);
        $this->assertSame(1, $result['usage']['users']['used']);
        $this->assertSame($this->company->max_users, $result['usage']['users']['max']);
        $this->assertSame((string) $this->company->uuid, $result['company']['uuid']);
    }

    public function test_super_admin_can_create_update_and_delete_a_plan_using_the_api(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $this->actingAs($superAdmin, 'sanctum');
        $response = $this->postJson('/api/v1/admin/plans', [
            'name' => 'Premium',
            'slug' => 'premium',
            'price_monthly' => 25,
            'price_yearly' => 240,
            'max_vehicles' => 20,
            'max_drivers' => 20,
            'max_users' => 10,
        ])->assertCreated();
        $uuid = $response->json('data.uuid');
        $this->getJson('/api/v1/admin/plans')->assertOk();
        $this->getJson('/api/v1/admin/plans/' . $uuid)->assertOk()->assertJsonPath('data.slug', 'premium');
        $this->getJson('/api/v1/plans/premium')->assertOk()->assertJsonPath('data.name', 'Premium');
        $this->putJson('/api/v1/admin/plans/' . $uuid, ['name' => 'Premium Plus'])
            ->assertOk()->assertJsonPath('data.name', 'Premium Plus');
        $this->deleteJson('/api/v1/admin/plans/' . $uuid)->assertOk();
        $this->assertDatabaseMissing('plans', ['uuid' => $uuid]);
    }
}
