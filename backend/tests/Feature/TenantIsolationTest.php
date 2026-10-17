<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/vehicles');
        $response->assertStatus(401);
    }

    public function test_user_cannot_access_other_company_data(): void
    {
        // Create another company with its own vehicle
        $otherCompany = $this->createCompany(['email' => 'other@iso.com']);

        \App\Modules\Vehicle\Models\Vehicle::create([
            'uuid'         => \Illuminate\Support\Str::uuid(),
            'company_id'   => $otherCompany->id,
            'plate_number' => 'OTHER-001',
            'brand'        => 'Mercedes',
            'model'        => 'Sprinter',
            'year'         => 2024,
            'type'         => 'van',
            'fuel_type'    => 'diesel',
        ]);

        // Admin of company 1 should see 0 vehicles
        $response = $this->actingAsAdmin()->getJson('/api/v1/vehicles');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_suspended_company_blocked(): void
    {
        $this->company->update(['status' => 'suspended', 'is_active' => false]);

        // Even authenticated, a user of a suspended company should be blocked
        $response = $this->actingAsAdmin()->getJson('/api/v1/vehicles');

        // The tenant middleware should block (403)
        $response->assertStatus(403);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->admin->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $this->admin->email,
            'password' => 'password',
        ]);

        // Should be denied (either 401 or 403)
        $this->assertTrue(in_array($response->status(), [401, 403]));
    }
}
