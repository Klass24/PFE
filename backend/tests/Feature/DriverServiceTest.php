<?php

namespace Tests\Feature;

use App\Modules\Driver\Repositories\DriverRepository;
use App\Modules\Driver\Services\DriverService;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class DriverServiceTest extends TestCase
{
    public function test_create_driver_with_new_user_and_manage_driver(): void
    {
        $this->actingAsAdmin();
        $notifications = Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyManagers')->once()
            ->with($this->company->id, 'license_expired', Mockery::type('string'));
        $service = new DriverService(new DriverRepository(), $notifications);
        $driver = $service->create([
            'license_number' => 'DRIVER-001',
            'license_expiry' => now()->addYear()->toDateString(),
            'status' => 'available',
            'user' => ['name' => 'New Driver', 'email' => 'new-driver@example.test', 'password' => 'password123'],
        ]);

        $this->assertSame('New Driver', $driver->user->name);
        $this->assertTrue($driver->user->hasRole('driver'));
        $this->assertSame(1, $service->paginate(['name' => 'New Driver', 'license_number' => '001'])->total());
        $driver = $service->update($driver, ['license_expiry' => now()->subDays(5)->toDateString()]);
        $this->assertSame('DRIVER-001', $driver->license_number);
        $service->delete($driver);
        $this->assertSoftDeleted('drivers', ['id' => $driver->id]);
    }

    public function test_driver_quota_failure_rolls_back_new_user(): void
    {
        $this->company->update(['max_drivers' => 0]);
        $this->actingAsAdmin();
        $service = new DriverService(new DriverRepository(), Mockery::mock(NotificationService::class));

        try {
            $service->create([
                'license_number' => 'OVER-QUOTA',
                'license_expiry' => now()->addYear()->toDateString(),
                'user' => ['name' => 'Over Quota', 'email' => 'over-quota@example.test', 'password' => 'password123'],
            ]);
            $this->fail('Creating a driver over quota should fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('company', $exception->errors());
        }

        $this->assertDatabaseMissing('users', ['email' => 'over-quota@example.test']);
        $this->assertDatabaseMissing('drivers', ['license_number' => 'OVER-QUOTA']);
    }

    public function test_driver_api_creates_updates_and_deletes_driver(): void
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/v1/drivers', [
            'license_number' => 'API-LICENSE',
            'license_expiry' => now()->addYear()->toDateString(),
            'status' => 'available',
            'user' => [
                'name' => 'API Driver',
                'email' => 'api-driver@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
        ])->assertCreated();
        $uuid = $response->json('data.uuid');
        $this->getJson('/api/v1/drivers?name=API')->assertOk()->assertJsonFragment(['uuid' => $uuid]);
        $this->getJson('/api/v1/drivers/' . $uuid)->assertOk()->assertJsonPath('data.uuid', $uuid);
        $this->putJson('/api/v1/drivers/' . $uuid, ['phone' => '123456'])
            ->assertOk()->assertJsonPath('data.phone', '123456');
        $this->deleteJson('/api/v1/drivers/' . $uuid)->assertOk();
        $this->assertSoftDeleted('drivers', ['uuid' => $uuid]);
        $this->postJson('/api/v1/drivers', [])->assertUnprocessable();
    }
}
