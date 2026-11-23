<?php

namespace Tests\Feature;

use App\Modules\Checklist\Repositories\ChecklistRepository;
use App\Modules\Checklist\Services\ChecklistService;
use App\Modules\Driver\Models\Driver;
use App\Modules\Mission\Models\Mission;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ChecklistServiceTest extends TestCase
{
    public function test_checklist_requires_photo_before_submission_and_validation(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'plate_number' => 'CHECK-001',
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
            'license_number' => 'CHECK-LIC',
            'license_expiry' => now()->addYear(),
            'status' => 'available',
        ]);
        $mission = Mission::create([
            'company_id' => $this->company->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'origin' => 'A',
            'destination' => 'B',
            'status' => 'planned',
        ]);
        $service = new ChecklistService(new ChecklistRepository());
        $checklist = $service->createForMission($mission);
        $url = '/api/v1/missions/' . $mission->uuid . '/checklist';
        $this->getJson($url)->assertOk()->assertJsonPath('data.status', 'draft');
        $this->assertCount(8, $checklist->items);
        $this->assertSame($checklist->id, $service->getForMission($mission)->id);
        $this->assertFalse($checklist->isFullyCompleted());

        try {
            $service->submit($checklist);
            $this->fail('A required photo must be present before submission.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('photos', $exception->errors());
        }

        $item = $checklist->items->firstWhere('requires_photo', true);
        $this->putJson($url . '/items', ['items' => [['id' => $item->id, 'is_ok' => true]]])
            ->assertOk()->assertJsonPath('data.status', 'draft');
        $this->postJson($url . '/items/' . $item->id . '/photo', [
            'photo' => UploadedFile::fake()->image('api-photo.jpg'),
        ])->assertOk()->assertJsonPath('data.has_photo', true);
        $item = $item->fresh();
        $item = $service->uploadItemPhoto($item, UploadedFile::fake()->image('photo.jpg'));
        Storage::disk('public')->assertExists($item->photo_path);
        $oldPath = $item->photo_path;
        $item = $service->uploadItemPhoto($item, UploadedFile::fake()->image('new-photo.jpg'));
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($item->photo_path);
        $checklist = $service->updateItems($checklist, [['id' => $item->id, 'is_ok' => true, 'note' => 'Checked']]);
        $this->assertTrue($checklist->items->firstWhere('id', $item->id)->is_ok);
        $this->assertTrue($checklist->isFullyCompleted());
        $this->postJson($url . '/submit', ['device_id' => 'device-1'])
            ->assertOk()->assertJsonPath('data.status', 'submitted');
        $checklist = $checklist->fresh();
        $this->assertSame('submitted', $checklist->status);
        $this->assertSame('device-1', $checklist->device_id);
        $this->postJson($url . '/validate')->assertOk()->assertJsonPath('data.status', 'validated');
    }

    public function test_standalone_checklist_and_validation(): void
    {
        $this->actingAsAdmin()->getJson('/api/v1/checklist/current')
            ->assertOk()->assertJsonCount(18, 'data');
        $this->postJson('/api/v1/checklist/validate', [
            'items' => [['id' => '1', 'is_checked' => true]],
        ])->assertOk()->assertJsonPath('data.validated', true);
        $this->postJson('/api/v1/checklist/validate', ['items' => [['id' => '1']]])
            ->assertUnprocessable();
    }
}
