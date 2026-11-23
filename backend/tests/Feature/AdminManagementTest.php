<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    public function test_super_admin_manages_company_and_subscription_lifecycle(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $this->actingAs($superAdmin, 'sanctum');
        $response = $this->postJson('/api/v1/admin/companies', [
            'name' => 'Managed Company',
            'email' => 'managed@example.test',
            'plan' => 'starter',
        ])->assertCreated();
        $uuid = $response->json('data.uuid');
        $company = Company::where('uuid', $uuid)->firstOrFail();
        $this->assertDatabaseHas('subscriptions', ['company_id' => $company->id, 'plan' => 'starter', 'billing_status' => 'active']);

        $this->getJson('/api/v1/admin/companies?search=Managed&plan=starter')
            ->assertOk()->assertJsonFragment(['name' => 'Managed Company']);
        $this->getJson('/api/v1/admin/companies/' . $uuid)->assertOk()->assertJsonPath('data.plan', 'starter');
        $this->putJson('/api/v1/admin/companies/' . $uuid, ['name' => 'Renamed Company', 'plan' => 'pro'])
            ->assertOk()->assertJsonPath('data.name', 'Renamed Company');
        $this->putJson('/api/v1/admin/companies/' . $uuid . '/suspend')->assertOk()->assertJsonPath('data.is_active', false);
        $this->putJson('/api/v1/admin/companies/' . $uuid . '/activate')->assertOk()->assertJsonPath('data.is_active', true);

        $this->getJson('/api/v1/admin/companies/' . $uuid . '/subscriptions/current')->assertOk();
        $this->getJson('/api/v1/admin/companies/' . $uuid . '/subscriptions?billing_status=active')->assertOk();
        $this->getJson('/api/v1/admin/companies/' . $uuid . '/quotas')->assertOk()->assertJsonPath('data.plan', 'pro');
        $subscription = $this->postJson('/api/v1/admin/companies/' . $uuid . '/subscriptions', [
            'plan' => 'enterprise',
            'start_date' => '2025-01-01',
            'end_date' => '2030-01-01',
            'billing_status' => 'active',
        ])->assertCreated();
        $id = $subscription->json('data.id');
        $this->assertSame('enterprise', $company->fresh()->plan);
        $this->getJson('/api/v1/admin/subscriptions?billing_status=active&plan=enterprise')->assertOk();
        $this->putJson('/api/v1/admin/subscriptions/' . $id, ['billing_status' => 'cancelled'])->assertOk();
        $this->deleteJson('/api/v1/admin/subscriptions/' . $id)->assertOk();
        $this->assertDatabaseMissing('subscriptions', ['id' => $id]);
        $this->postJson('/api/v1/admin/companies/' . $uuid . '/subscriptions/change-plan', ['plan' => 'pro'])
            ->assertOk()->assertJsonPath('data.plan', 'pro');
        $this->deleteJson('/api/v1/admin/companies/' . $uuid)->assertOk();
        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_non_super_admin_cannot_manage_companies(): void
    {
        $this->actingAsAdmin()->getJson('/api/v1/admin/companies')->assertForbidden();
    }
}
