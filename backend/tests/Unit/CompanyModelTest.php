<?php

namespace Tests\Unit;

use App\Modules\Company\Models\Company;
use Tests\TestCase;

class CompanyModelTest extends TestCase
{
    public function test_plan_quotas_applied_on_create(): void
    {
        $company = Company::create([
            'name'      => 'Starter Co',
            'email'     => 'starter@test.com',
            'plan'      => 'starter',
            'is_active' => true,
        ]);

        $this->assertEquals(5, $company->max_vehicles);
        $this->assertEquals(5, $company->max_drivers);
        $this->assertEquals(3, $company->max_users);
    }

    public function test_plan_change_updates_quotas(): void
    {
        $company = Company::create([
            'name'      => 'Upgrade Co',
            'email'     => 'upgrade@test.com',
            'plan'      => 'starter',
            'is_active' => true,
        ]);

        $company->update(['plan' => 'enterprise']);

        $this->assertEquals(999, $company->fresh()->max_vehicles);
        $this->assertEquals(999, $company->fresh()->max_drivers);
    }

    public function test_suspend_company(): void
    {
        $company = Company::create([
            'name'      => 'Suspend Co',
            'email'     => 'suspend@test.com',
            'plan'      => 'pro',
            'is_active' => true,
        ]);

        $company->suspend();

        $this->assertTrue($company->isSuspended());
        $this->assertFalse($company->is_active);
    }

    public function test_activate_company(): void
    {
        $company = Company::create([
            'name'      => 'Activate Co',
            'email'     => 'activate@test.com',
            'plan'      => 'pro',
            'is_active' => false,
            'status'    => 'suspended',
        ]);

        $company->activate();

        $this->assertFalse($company->isSuspended());
        $this->assertTrue($company->is_active);
    }
}
