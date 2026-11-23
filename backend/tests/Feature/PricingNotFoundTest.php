<?php

namespace Tests\Feature;

use App\Modules\Pricing\Models\Plan;
use App\Modules\Pricing\Repositories\PlanRepositoryInterface;
use Illuminate\Foundation\Testing\TestCase;
use Mockery;

class PricingNotFoundTest extends TestCase
{
    public function test_unknown_plan_returns_a_not_found_response(): void
    {
        $repository = Mockery::mock(PlanRepositoryInterface::class);
        $repository->shouldReceive('findBySlug')->once()->with('unknown')->andReturn(null);
        $this->app->instance(PlanRepositoryInterface::class, $repository);

        $this->getJson('/api/v1/plans/unknown')
            ->assertNotFound()
            ->assertExactJson([
                'success' => false,
                'data' => null,
                'message' => 'Plan introuvable.',
            ]);
    }

    public function test_existing_plan_can_still_be_retrieved(): void
    {
        $plan = new Plan([
            'uuid' => 'test-uuid',
            'name' => 'Test plan',
            'slug' => 'test-plan',
            'price_monthly' => 10,
            'price_yearly' => 100,
        ]);
        $repository = Mockery::mock(PlanRepositoryInterface::class);
        $repository->shouldReceive('findBySlug')->once()->with('test-plan')->andReturn($plan);
        $this->app->instance(PlanRepositoryInterface::class, $repository);

        $this->getJson('/api/v1/plans/test-plan')
            ->assertOk()
            ->assertJsonPath('data.slug', 'test-plan');
    }
}
