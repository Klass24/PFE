<?php

namespace Tests\Feature;

use App\Modules\Document\Models\Document;
use App\Jobs\CheckDocumentExpiryJob;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    public function test_upload_list_view_and_delete_vehicle_document(): void
    {
        Storage::fake('public');
        $this->actingAsAdmin();
        $vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'plate_number' => 'DOC-001',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2024,
            'type' => 'truck',
            'fuel_type' => 'diesel',
        ]);
        $response = $this->postJson('/api/v1/documents', [
            'documentable_type' => 'vehicle',
            'documentable_uuid' => (string) $vehicle->uuid,
            'type' => 'insurance',
            'file' => UploadedFile::fake()->create('insurance.pdf', 5, 'application/pdf'),
            'expiry_date' => now()->addDays(10)->toDateString(),
        ])->assertCreated();
        $uuid = $response->json('data.uuid');
        $document = Document::where('uuid', $uuid)->firstOrFail();
        Storage::disk('public')->assertExists($document->file_path);
        $this->assertSame($vehicle->id, $document->documentable_id);

        $this->getJson('/api/v1/documents?type=insurance&status=expiring')->assertOk()->assertJsonFragment(['uuid' => $uuid]);
        $this->getJson('/api/v1/documents/expiring')->assertOk()->assertJsonFragment(['uuid' => $uuid]);
        $this->getJson('/api/v1/documents/' . $uuid)->assertOk()->assertJsonPath('data.uuid', $uuid);
        $this->deleteJson('/api/v1/documents/' . $uuid)->assertOk();
        $this->assertDatabaseMissing('documents', ['uuid' => $uuid]);
        Storage::disk('public')->assertMissing($document->file_path);
    }

    public function test_document_upload_requires_a_file(): void
    {
        $this->actingAsAdmin()->postJson('/api/v1/documents', [
            'documentable_type' => 'vehicle',
            'documentable_uuid' => 'missing',
            'type' => 'insurance',
        ])->assertUnprocessable();
    }

    public function test_expiry_job_notifies_for_thresholds_once(): void
    {
        $vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'plate_number' => 'DOC-EXPIRING',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2024,
            'type' => 'truck',
            'fuel_type' => 'diesel',
        ]);
        foreach ([7, 30, 10] as $days) {
            Document::create([
                'company_id' => $this->company->id,
                'documentable_type' => Vehicle::class,
                'documentable_id' => $vehicle->id,
                'type' => 'insurance',
                'file_path' => 'insurance-' . $days . '.pdf',
                'expiry_date' => now()->addDays($days)->toDateString(),
                'alert_sent' => false,
            ]);
        }
        $notifications = Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifyManagers')->twice()
            ->with($this->company->id, 'document_expiring', Mockery::pattern('/DOC-EXPIRING/'));

        $job = new CheckDocumentExpiryJob();
        $job->handle($notifications);
        $job->handle($notifications);
        $this->assertDatabaseHas('documents', ['file_path' => 'insurance-7.pdf', 'alert_sent' => true]);
        $this->assertDatabaseHas('documents', ['file_path' => 'insurance-30.pdf', 'alert_sent' => true]);
        $this->assertDatabaseHas('documents', ['file_path' => 'insurance-10.pdf', 'alert_sent' => false]);
    }
}
