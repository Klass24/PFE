<?php

namespace Tests\Unit;

use App\Modules\Checklist\Models\Checklist;
use App\Modules\Checklist\Repositories\ChecklistRepositoryInterface;
use App\Modules\Checklist\Services\ChecklistService;
use App\Modules\ApiToken\Services\ApiTokenService;
use App\Modules\AlertRules\Models\AlertRule;
use App\Modules\Admin\Services\AdminService;
use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Repositories\DocumentRepositoryInterface;
use App\Modules\Document\Services\DocumentService;
use App\Modules\Expense\Repositories\ExpenseRepositoryInterface;
use App\Modules\Expense\Services\ExpenseService;
use App\Modules\FuelMonitoring\Services\FuelMonitoringService;
use App\Modules\Incident\Repositories\IncidentRepositoryInterface;
use App\Modules\Incident\Services\IncidentService;
use App\Modules\Metrics\Controllers\MetricsController;
use App\Modules\Mission\Models\Mission;
use App\Modules\Mission\Repositories\MissionRepositoryInterface;
use App\Modules\Mission\Services\MissionService;
use App\Modules\Payment\Services\PayPalService;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Services\StripeService;
use App\Modules\Pricing\Repositories\PlanRepositoryInterface;
use App\Modules\Pricing\Services\PricingService;
use App\Modules\Subscription\Services\SubscriptionService;
use App\Modules\Tracking\Models\Geofence;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Vehicle\Models\VehicleStatusLog;
use App\Modules\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicle\Services\VehicleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ModuleServicesTest extends TestCase
{
    public function test_checklist_cannot_be_validated_before_submission(): void
    {
        $repository = Mockery::mock(ChecklistRepositoryInterface::class);
        $repository->shouldNotReceive('update');

        $checklist = new Checklist(['status' => 'draft']);

        try {
            (new ChecklistService($repository))->validate($checklist);
            $this->fail('A draft checklist must not be validated.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }
    }

    public function test_mission_cannot_start_without_a_validated_checklist(): void
    {
        $mission = new Mission();
        $mission->id = 42;

        $missionRepository = Mockery::mock(MissionRepositoryInterface::class);
        $missionRepository->shouldNotReceive('update');

        $checklistRepository = Mockery::mock(ChecklistRepositoryInterface::class);
        $checklistRepository->shouldReceive('findByMission')
            ->once()
            ->with(42)
            ->andReturn(null);

        $service = new MissionService($missionRepository, $checklistRepository);

        try {
            $service->start($mission);
            $this->fail('A mission without a validated checklist must not start.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('checklist', $exception->errors());
        }
    }

    public function test_expenses_are_rejected_for_completed_missions(): void
    {
        $repository = Mockery::mock(ExpenseRepositoryInterface::class);
        $repository->shouldNotReceive('create');

        $mission = new Mission(['status' => 'completed']);

        try {
            (new ExpenseService($repository))->create($mission, ['amount' => 10]);
            $this->fail('An expense must not be added to a completed mission.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('mission_id', $exception->errors());
        }
    }

    public function test_incidents_are_rejected_for_completed_missions(): void
    {
        $repository = Mockery::mock(IncidentRepositoryInterface::class);
        $repository->shouldNotReceive('create');

        $mission = new Mission(['status' => 'completed']);

        try {
            (new IncidentService($repository))->create($mission, ['type' => 'other']);
            $this->fail('An incident must not be reported on a completed mission.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('mission_id', $exception->errors());
        }
    }

    public function test_pricing_service_returns_a_not_found_response_for_unknown_plan(): void
    {
        $repository = Mockery::mock(PlanRepositoryInterface::class);
        $repository->shouldReceive('findByUuid')
            ->once()
            ->with('unknown')
            ->andReturn(null);

        try {
            (new PricingService($repository))->getByUuid('unknown');
            $this->fail('An unknown plan must return a not found response.');
        } catch (NotFoundHttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
            $this->assertSame('Plan introuvable.', $exception->getMessage());
        }
    }

    public function test_payment_service_routes_checkout_to_the_selected_gateway(): void
    {
        $company = new \App\Modules\Company\Models\Company();
        $plan = new \App\Modules\Pricing\Models\Plan();
        $result = ['checkout_url' => 'https://example.test/checkout'];

        $stripe = Mockery::mock(StripeService::class);
        $stripe->shouldReceive('createCheckoutSession')
            ->once()
            ->with($company, $plan, 'yearly')
            ->andReturn($result);

        $paypal = Mockery::mock(PayPalService::class);
        $paypal->shouldNotReceive('createOrder');

        $this->assertSame(
            $result,
            (new PaymentService($stripe, $paypal))
                ->initiateCheckout($company, $plan, 'stripe', 'yearly')
        );
    }

    public function test_payment_service_rejects_an_unsupported_gateway(): void
    {
        $service = new PaymentService(
            Mockery::mock(StripeService::class),
            Mockery::mock(PayPalService::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        $service->initiateCheckout(new \App\Modules\Company\Models\Company(), new \App\Modules\Pricing\Models\Plan(), 'unknown');
    }

    public function test_document_service_deletes_the_file_before_deleting_the_record(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('documents/old.pdf', 'contents');

        $document = new Document(['file_path' => 'documents/old.pdf']);

        $repository = Mockery::mock(DocumentRepositoryInterface::class);
        $repository->shouldReceive('delete')
            ->once()
            ->with($document);

        (new DocumentService($repository))->delete($document);

        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('documents/old.pdf');
    }

    public function test_document_service_delegates_the_expiry_window_to_its_repository(): void
    {
        $documents = new Collection();
        $repository = Mockery::mock(DocumentRepositoryInterface::class);
        $repository->shouldReceive('getExpiringSoon')
            ->once()
            ->with(30)
            ->andReturn($documents);

        $this->assertSame($documents, (new DocumentService($repository))->getExpiringSoon());
    }

    public function test_driver_and_maintenance_services_delegate_pagination_filters(): void
    {
        $filters = ['status' => 'active'];
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $driverRepository = Mockery::mock(\App\Modules\Driver\Repositories\DriverRepositoryInterface::class);
        $driverRepository->shouldReceive('paginate')->once()->with($filters)->andReturn($paginator);
        $driverService = new \App\Modules\Driver\Services\DriverService(
            $driverRepository,
            Mockery::mock(\App\Modules\Notification\Services\NotificationService::class),
        );

        $maintenanceRepository = Mockery::mock(\App\Modules\Maintenance\Repositories\MaintenanceRepositoryInterface::class);
        $maintenanceRepository->shouldReceive('paginate')->once()->with($filters)->andReturn($paginator);
        $maintenanceService = new \App\Modules\Maintenance\Services\MaintenanceService($maintenanceRepository);

        $this->assertSame($paginator, $driverService->paginate($filters));
        $this->assertSame($paginator, $maintenanceService->paginate($filters));
    }

    public function test_api_token_generation_returns_a_single_use_plaintext_token_and_stores_its_hash(): void
    {
        $this->actingAsAdmin();

        $result = (new ApiTokenService())->generate(['name' => 'Integration']);

        $this->assertSame(64, strlen($result['plainText']));
        $this->assertSame(hash('sha256', $result['plainText']), $result['token']->token);
        $this->assertSame($this->company->id, $result['token']->company_id);
    }

    public function test_notification_service_can_mark_a_notification_as_read(): void
    {
        $this->actingAsAdmin();
        $service = new \App\Modules\Notification\Services\NotificationService();

        $notification = $service->create($this->company->id, 'test', 'Unit test', $this->admin->id);
        $service->markAsRead($notification);

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_alert_rule_evaluation_checks_each_condition(): void
    {
        $rule = new AlertRule([
            'conditions' => [
                'amount' => ['operator' => '>=', 'value' => 100],
                'type' => ['operator' => 'in', 'value' => ['fuel', 'repair']],
            ],
        ]);

        $this->assertTrue($rule->evaluate(['amount' => 100, 'type' => 'fuel']));
        $this->assertFalse($rule->evaluate(['amount' => 99, 'type' => 'fuel']));
        $this->assertFalse($rule->evaluate(['amount' => 100, 'type' => 'other']));
    }

    public function test_subscription_service_reports_company_quota_usage(): void
    {
        $usage = (new SubscriptionService())->getQuotaUsageForCompany($this->company);

        $this->assertSame('pro', $usage['plan']);
        $this->assertSame(0, $usage['vehicles']['used']);
        $this->assertSame(1, $usage['users']['used']);
        $this->assertSame($this->company->max_users, $usage['users']['max']);
    }

    public function test_admin_service_returns_expected_statistics_sections(): void
    {
        $stats = (new AdminService())->getStats();

        $this->assertArrayHasKey('total_companies', $stats);
        $this->assertArrayHasKey('active_companies', $stats);
        $this->assertArrayHasKey('suspended_companies', $stats);
        $this->assertArrayHasKey('companies_by_plan', $stats);
        $this->assertArrayHasKey('recent_companies', $stats);
        $this->assertSame(1, $stats['total_companies']);
    }

    public function test_dashboard_service_returns_expected_statistics_sections(): void
    {
        $stats = (new DashboardService())->getStats($this->company->id);

        $this->assertArrayHasKey('total_vehicles', $stats);
        $this->assertArrayHasKey('active_missions', $stats);
        $this->assertArrayHasKey('total_expenses_month', $stats);
        $this->assertArrayHasKey('expired_documents', $stats);
        $this->assertSame(0, $stats['total_vehicles']);
    }

    public function test_fuel_monitoring_returns_an_empty_page_when_no_entries_exist(): void
    {
        $this->actingAsAdmin();

        $page = (new FuelMonitoringService())->paginate();

        $this->assertSame(0, $page->total());
        $this->assertSame(15, $page->perPage());
    }

    public function test_geofence_detects_points_inside_and_outside_a_circle(): void
    {
        $geofence = new Geofence([
            'type' => 'circle',
            'center_lat' => 48.8566,
            'center_lng' => 2.3522,
            'radius_meters' => 500,
        ]);

        $this->assertTrue($geofence->containsPoint(48.857, 2.352));
        $this->assertFalse($geofence->containsPoint(48.866, 2.352));
    }

    public function test_audit_log_casts_value_snapshots_to_arrays(): void
    {
        $log = new AuditLog([
            'old_values' => ['status' => 'planned'],
            'new_values' => ['status' => 'completed'],
        ]);

        $this->assertSame(['status' => 'planned'], $log->old_values);
        $this->assertSame(['status' => 'completed'], $log->new_values);
    }

    public function test_role_permissions_are_assigned_by_role(): void
    {
        $driver = $this->createUser('driver');

        $this->assertTrue($this->admin->hasPermissionTo('vehicles.view'));
        $this->assertFalse($driver->hasPermissionTo('vehicles.view'));
    }

    public function test_metrics_controller_returns_prometheus_text(): void
    {
        $response = (new MetricsController())();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('http_requests_total 0', $response->getContent());
        $this->assertStringContainsString('app_uptime_seconds 1', $response->getContent());
        $this->assertStringContainsString('text/plain; version=0.0.4', $response->headers->get('Content-Type'));
    }

    public function test_vehicle_service_records_status_changes(): void
    {
        $this->actingAsAdmin();
        $vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'plate_number' => 'TEST-STATUS-1',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2024,
            'fuel_type' => 'diesel',
            'status' => 'available',
        ]);

        $repository = Mockery::mock(VehicleRepositoryInterface::class);
        $repository->shouldReceive('update')
            ->once()
            ->with($vehicle, ['status' => 'maintenance'])
            ->andReturnUsing(function (Vehicle $model, array $data): Vehicle {
                $model->fill($data);
                return $model;
            });

        $updated = (new VehicleService($repository))->update($vehicle, ['status' => 'maintenance']);

        $this->assertSame('maintenance', $updated->status);
        $this->assertDatabaseHas('vehicle_status_logs', [
            'vehicle_id' => $vehicle->id,
            'company_id' => $this->company->id,
            'old_status' => 'available',
            'new_status' => 'maintenance',
            'changed_by' => $this->admin->id,
        ]);
    }

    public function test_maintenance_service_calculates_next_due_dates_from_frequency(): void
    {
        $this->actingAsAdmin();
        $vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'plate_number' => 'TEST-MAINT-1',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2024,
            'fuel_type' => 'diesel',
            'status' => 'available',
        ]);

        $repository = Mockery::mock(\App\Modules\Maintenance\Repositories\MaintenanceRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data) use ($vehicle): bool {
                return $data['vehicle_id'] === $vehicle->id
                    && $data['company_id'] === $this->company->id
                    && $data['next_due_date'] === '2026-07-15'
                    && $data['next_due_km'] === 25000
                    && !array_key_exists('vehicle_uuid', $data);
            }))
            ->andReturnUsing(fn (array $data) => new \App\Modules\Maintenance\Models\MaintenanceSchedule($data));

        $schedule = (new \App\Modules\Maintenance\Services\MaintenanceService($repository))->create([
            'vehicle_uuid' => $vehicle->uuid,
            'maintenance_type' => 'oil_change',
            'frequency_months' => 6,
            'last_service_date' => '2026-01-15',
            'frequency_km' => 10000,
            'last_service_km' => 15000,
        ]);

        $this->assertSame('2026-07-15', $schedule->next_due_date->toDateString());
        $this->assertSame(25000, $schedule->next_due_km);
    }
}
