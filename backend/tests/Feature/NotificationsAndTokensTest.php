<?php

namespace Tests\Feature;

use App\Modules\ApiToken\Models\ApiToken;
use App\Modules\Notification\Services\NotificationService;
use Tests\TestCase;

class NotificationsAndTokensTest extends TestCase
{
    public function test_notifications_are_listed_and_marked_read_for_the_current_user(): void
    {
        $this->actingAsAdmin();
        $service = new NotificationService();
        $first = $service->create($this->company->id, 'test', 'First', $this->admin->id);
        $second = $service->create($this->company->id, 'test', 'Second', $this->admin->id);
        $driver = $this->createUser('driver');
        $other = $service->create($this->company->id, 'test', 'Other user', $driver->id);

        $this->getJson('/api/v1/notifications?unread_only=1')->assertOk()->assertJsonFragment(['message' => 'First']);
        $this->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.count', 2);
        $this->postJson('/api/v1/notifications/' . $first->uuid . '/read')->assertOk();
        $this->postJson('/api/v1/notifications/read-all')->assertOk()->assertJsonPath('data.updated', 1);
        $this->assertTrue($first->fresh()->is_read);
        $this->assertTrue($second->fresh()->is_read);
        $this->assertFalse($other->fresh()->is_read);
        $this->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.count', 0);
    }

    public function test_admin_can_create_list_and_revoke_a_hashed_api_token(): void
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/v1/api-tokens', ['name' => 'Integration', 'abilities' => ['read']])
            ->assertCreated();
        $uuid = $response->json('data.token.uuid');
        $plainText = $response->json('data.plain_text');
        $token = ApiToken::where('uuid', $uuid)->firstOrFail();
        $this->assertSame(hash('sha256', $plainText), $token->token);
        $this->getJson('/api/v1/api-tokens')->assertOk()->assertJsonFragment(['uuid' => $uuid]);
        $this->deleteJson('/api/v1/api-tokens/' . $uuid)->assertOk();
        $this->assertDatabaseMissing('api_tokens', ['uuid' => $uuid]);
        $this->postJson('/api/v1/api-tokens', [])->assertUnprocessable();
    }

    public function test_driver_cannot_create_or_list_api_tokens(): void
    {
        $this->actingAs($this->createUser('driver'), 'sanctum');
        $this->getJson('/api/v1/api-tokens')->assertForbidden();
        $this->postJson('/api/v1/api-tokens', ['name' => 'Forbidden'])->assertForbidden();
    }
}
