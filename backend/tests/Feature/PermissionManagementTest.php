<?php

namespace Tests\Feature;

use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    public function test_super_admin_manages_custom_role_and_role_permissions(): void
    {
        $superAdmin = $this->createUser('super_admin');
        $this->actingAs($superAdmin, 'sanctum');
        $this->getJson('/api/v1/permissions')->assertOk()->assertJsonPath('data.vehicles.0', 'vehicles.create');
        $response = $this->postJson('/api/v1/roles', [
            'name' => 'auditor',
            'permissions' => ['vehicles.view'],
        ])->assertCreated();
        $id = $response->json('data.id');
        $this->assertDatabaseHas('roles', ['id' => $id, 'name' => 'auditor']);
        $protected = Role::where('name', 'driver')->where('guard_name', 'sanctum')->firstOrFail();
        $this->deleteJson('/api/v1/roles/' . $protected->id)->assertUnprocessable();
        $this->getJson('/api/v1/roles/manager/permissions')->assertOk()->assertJsonPath('data.role', 'manager');
        $this->putJson('/api/v1/roles/manager/permissions', ['permissions' => ['vehicles.view']])
            ->assertOk()->assertJsonPath('data.permissions.0', 'vehicles.view');
        $this->putJson('/api/v1/roles/driver/permissions', ['permissions' => []])->assertUnprocessable();
    }

    public function test_admin_can_manage_driver_permissions_but_not_other_admins(): void
    {
        $driver = $this->createUser('driver');
        $this->actingAsAdmin();
        $this->getJson('/api/v1/users/' . $driver->uuid . '/permissions')
            ->assertOk()->assertJsonPath('data.role', 'driver');
        $this->putJson('/api/v1/users/' . $driver->uuid . '/permissions', ['permissions' => ['vehicles.view']])
            ->assertOk()->assertJsonFragment(['vehicles.view']);
        $this->putJson('/api/v1/users/' . $this->admin->uuid . '/permissions', ['permissions' => []])
            ->assertForbidden();
        $this->getJson('/api/v1/roles')->assertForbidden();
        $this->getJson('/api/v1/roles/manager/permissions')->assertForbidden();
    }
}
