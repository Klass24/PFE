<?php

namespace Tests\Feature;

use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_admin_can_list_users(): void
    {
        $response = $this->actingAsAdmin()->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/users', [
                'name'     => 'New User',
                'email'    => 'newuser@test.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role'     => 'manager',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'email'      => 'newuser@test.com',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $user = $this->createUser('manager');

        $response = $this->actingAsAdmin()
            ->putJson("/api/v1/users/{$user->uuid}", [
                'name' => 'Updated Name',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_delete_user(): void
    {
        $user = $this->createUser('driver');

        $response = $this->actingAsAdmin()
            ->deleteJson("/api/v1/users/{$user->uuid}");

        $response->assertOk();
    }

    public function test_driver_cannot_list_users(): void
    {
        $driver = $this->createUser('driver');

        $response = $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertStatus(403);
    }

    public function test_manager_cannot_create_users(): void
    {
        $manager = $this->createUser('manager');

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/v1/users', [
                'name'     => 'Attempt',
                'email'    => 'attempt@test.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role'     => 'driver',
            ]);

        $response->assertStatus(403);
    }

    public function test_create_user_validation_required_fields(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/users', []);

        $response->assertStatus(422);
    }

    public function test_create_user_duplicate_email(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/users', [
                'name'     => 'Duplicate',
                'email'    => $this->admin->email,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role'     => 'driver',
            ]);

        $response->assertStatus(422);
    }
}
