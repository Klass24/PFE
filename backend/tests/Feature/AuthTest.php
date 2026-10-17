<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $this->admin->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['token', 'user'],
                'message',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $this->admin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_login_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
    }

    public function test_logout_removes_token(): void
    {
        $headers = $this->authHeaders($this->admin);

        $response = $this->postJson('/api/v1/auth/logout', [], $headers);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $headers = $this->authHeaders($this->admin);

        $response = $this->getJson('/api/v1/auth/me', $headers);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => $this->admin->email,
                    'role'  => 'admin',
                ],
            ]);
    }

    public function test_me_returns_401_without_token(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_register_creates_company_and_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'company_name' => 'New Company',
            'company_email' => 'contact@newcompany.com',
            'name'         => 'John Doe',
            'email'        => 'john@newcompany.com',
            'password'     => 'Password123!',
            'password_confirmation' => 'Password123!',
            'plan'         => 'starter',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);
    }
}
