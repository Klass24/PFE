<?php

namespace Tests\Feature;

use App\Modules\Driver\Models\Driver;
use App\Modules\Mission\Models\Mission;
use App\Modules\Tracking\Models\Geofence;
use App\Modules\Tracking\Services\TrackingService;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class TrackingServiceTest extends TestCase
{
    private Mission $mission;
    private TrackingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
        $vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'plate_number' => 'TRACK-001',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2024,
            'type' => 'truck',
            'fuel_type' => 'diesel',
        ]);
        $user = $this->createUser('driver');
        $driver = Driver::create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
            'license_number' => 'TRACK-LIC',
            'license_expiry' => now()->addYear(),
            'status' => 'available',
        ]);
        $this->mission = Mission::create([
            'company_id' => $this->company->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'origin' => 'Brazzaville',
            'destination' => 'Dolisie',
            'status' => 'in_progress',
        ]);
        $this->service = new TrackingService();
    }

    public function test_records_points_and_returns_live_history_and_active_tracking(): void
    {
        $geofence = $this->service->createGeofence([
            'name' => 'Depot',
            'center_lat' => 0,
            'center_lng' => 0,
            'radius_meters' => 1000,
        ]);
        $this->assertTrue($geofence->containsPoint(0, 0));
        $this->assertFalse($geofence->containsPoint(1, 1));
        $this->assertSame([$geofence->id], $this->service->getGeofences()->modelKeys());

        $points = [
            ['mission_uuid' => (string) $this->mission->uuid, 'latitude' => 0, 'longitude' => 0, 'speed' => 20, 'recorded_at' => '2025-01-01 10:00:00'],
            ['mission_uuid' => (string) $this->mission->uuid, 'latitude' => 0, 'longitude' => 0.01, 'speed' => 40, 'recorded_at' => '2025-01-01 11:00:00'],
        ];
        $this->assertSame(2, $this->service->recordBatch($points));

        $live = $this->service->getMissionLive((string) $this->mission->uuid);
        $this->assertSame(2, $live['total_points']);
        $this->assertEquals(0.01, $live['latest']->longitude);

        $history = $this->service->getMissionHistory((string) $this->mission->uuid, '2025-01-01 09:00:00', '2025-01-01 12:00:00');
        $this->assertSame(2, $history['total_points']);
        $this->assertEqualsWithDelta(1.11, $history['total_distance'], 0.01);
        $this->assertEquals(40, $history['max_speed']);
        $this->assertEquals(30, $history['avg_speed']);
        $this->assertSame(1, $this->service->getMissionHistory((string) $this->mission->uuid, '2025-01-01 10:30:00')['total_points']);

        $active = $this->service->getActiveTracking();
        $this->assertCount(1, $active);
        $this->assertSame('TRACK-001', $active[0]->plate_number);
        $this->assertEquals(0.01, $active[0]->longitude);
    }

    public function test_batch_rolls_back_if_a_mission_does_not_exist(): void
    {
        try {
            $this->service->recordBatch([
                ['mission_uuid' => (string) $this->mission->uuid, 'latitude' => 0, 'longitude' => 0],
                ['mission_uuid' => 'unknown', 'latitude' => 0, 'longitude' => 0],
            ]);
            $this->fail('A batch containing an unknown mission must fail.');
        } catch (ModelNotFoundException $exception) {
            $this->assertDatabaseCount('tracking_points', 0);
        }
    }

    public function test_only_active_geofences_are_returned(): void
    {
        $active = $this->service->createGeofence(['name' => 'Active', 'center_lat' => 0, 'center_lng' => 0, 'radius_meters' => 10]);
        $inactive = $this->service->createGeofence(['name' => 'Inactive', 'center_lat' => 0, 'center_lng' => 0, 'radius_meters' => 10]);
        $inactive->update(['is_active' => false]);

        $this->assertSame([$active->id], $this->service->getGeofences()->modelKeys());
        $this->assertFalse((new Geofence(['type' => 'polygon']))->containsPoint(0, 0));
    }

    public function test_tracking_api_records_and_retrieves_positions_and_geofences(): void
    {
        $uuid = (string) $this->mission->uuid;
        $this->postJson('/api/v1/tracking/geofences', [
            'name' => 'Depot', 'type' => 'circle', 'center_lat' => 0, 'center_lng' => 0, 'radius_meters' => 500,
        ])->assertCreated()->assertJsonPath('data.name', 'Depot');
        $this->getJson('/api/v1/tracking/geofences')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/tracking/record-point', [
            'mission_uuid' => $uuid, 'latitude' => 0, 'longitude' => 0, 'speed' => 10,
        ])->assertCreated()->assertJsonPath('data.mission_id', $this->mission->id);
        $this->postJson('/api/v1/tracking/record-batch', [
            'points' => [['mission_uuid' => $uuid, 'latitude' => 0.1, 'longitude' => 0.1]],
        ])->assertCreated()->assertJsonPath('data.count', 1);
        $this->getJson('/api/v1/tracking/mission/' . $uuid . '/live')->assertOk()->assertJsonPath('data.total_points', 2);
        $this->getJson('/api/v1/tracking/mission/' . $uuid . '/history')->assertOk()->assertJsonPath('data.total_points', 2);
        $this->getJson('/api/v1/tracking/active')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson('/api/v1/tracking/record-point', [
            'mission_uuid' => $uuid, 'latitude' => 100, 'longitude' => 0,
        ])->assertUnprocessable();
    }
}
