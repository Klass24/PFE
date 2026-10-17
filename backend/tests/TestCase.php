<?php

namespace Tests;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        $this->company = $this->createCompany();
        $this->admin   = $this->createUser('admin');
    }

    protected function seedRolesAndPermissions(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.delete',
            'drivers.view', 'drivers.create', 'drivers.update', 'drivers.delete',
            'missions.view', 'missions.create', 'missions.update', 'missions.delete',
            'missions.start', 'missions.complete', 'missions.cancel',
            'expenses.view', 'expenses.create', 'expenses.update', 'expenses.delete',
            'incidents.view', 'incidents.create', 'incidents.update', 'incidents.delete',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete',
            'users.view', 'users.create', 'users.update', 'users.delete',
            'company.manage', 'reports.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'sanctum']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $adminRole->syncPermissions(Permission::all());

        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'sanctum']);
        $managerRole->syncPermissions([
            'vehicles.view', 'vehicles.create', 'vehicles.update',
            'drivers.view', 'drivers.create', 'drivers.update',
            'missions.view', 'missions.create', 'missions.update',
            'expenses.view', 'expenses.create', 'expenses.update',
            'incidents.view', 'incidents.create', 'incidents.update',
            'documents.view', 'documents.create', 'documents.update',
            'reports.view',
        ]);

        $driverRole = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'sanctum']);
        $driverRole->syncPermissions([
            'missions.view', 'expenses.view', 'expenses.create',
            'incidents.view', 'incidents.create',
        ]);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum'])
            ->syncPermissions(Permission::all());
    }

    protected function createCompany(array $overrides = []): Company
    {
        return Company::create(array_merge([
            'uuid'      => Str::uuid(),
            'name'      => 'Test Company',
            'slug'      => 'test-company-' . Str::random(5),
            'email'     => 'test@company.com',
            'plan'      => 'pro',
            'is_active' => true,
        ], $overrides));
    }

    protected function createUser(string $role = 'admin', array $overrides = []): User
    {
        $user = User::create(array_merge([
            'uuid'       => Str::uuid(),
            'company_id' => $this->company->id,
            'name'       => ucfirst($role) . ' User',
            'email'      => $role . '-' . Str::random(5) . '@test.com',
            'password'   => bcrypt('password'),
            'is_active'  => true,
        ], $overrides));

        $user->assignRole($role);

        return $user;
    }

    protected function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin, 'sanctum');
    }

    protected function authHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }
}
