<?php

namespace Tests\Unit;

use Tests\TestCase;

class UserServiceTest extends TestCase
{
    public function test_user_quota_enforcement(): void
    {
        // Starter plan allows max 3 users
        $starterCompany = $this->createCompany([
            'email' => 'starter-svc@test.com',
            'plan'  => 'starter',
        ]);
        $this->company = $starterCompany;

        // Company already has 0 users. Create admin (the setUp admin is for another company).
        $admin = $this->createUser('admin');

        // Create 2 more users (total = 3 = max)
        $this->createUser('manager', ['email' => 'u2@test.com']);
        $this->createUser('driver', ['email' => 'u3@test.com']);

        // 4th user should fail (quota = 3)
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/users', [
                'name'     => 'Over Quota',
                'email'    => 'overquota@test.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role'     => 'driver',
            ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_delete_self(): void
    {
        $response = $this->actingAsAdmin()
            ->deleteJson("/api/v1/users/{$this->admin->uuid}");

        $response->assertStatus(422);
    }

    public function test_password_is_hashed_on_create(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/users', [
                'name'     => 'Hash Test',
                'email'    => 'hashtest@test.com',
                'password' => 'MyPassword123!',
                'password_confirmation' => 'MyPassword123!',
                'role'     => 'driver',
            ]);

        $response->assertStatus(201);

        // Verify the password is NOT stored in plain text
        $user = \App\Modules\User\Models\User::where('email', 'hashtest@test.com')->first();
        $this->assertNotEquals('MyPassword123!', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('MyPassword123!', $user->password));
    }
}
