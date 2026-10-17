<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

// Company
use App\Modules\Company\Repositories\CompanyRepositoryInterface;
use App\Modules\Company\Repositories\CompanyRepository;

// User
use App\Modules\User\Repositories\UserRepositoryInterface;
use App\Modules\User\Repositories\UserRepository;

// Vehicle
use App\Modules\Vehicle\Repositories\VehicleRepositoryInterface;
use App\Modules\Vehicle\Repositories\VehicleRepository;

// Driver
use App\Modules\Driver\Repositories\DriverRepositoryInterface;
use App\Modules\Driver\Repositories\DriverRepository;

// Mission
use App\Modules\Mission\Repositories\MissionRepositoryInterface;
use App\Modules\Mission\Repositories\MissionRepository;

// Expense
use App\Modules\Expense\Repositories\ExpenseRepositoryInterface;
use App\Modules\Expense\Repositories\ExpenseRepository;

// Incident
use App\Modules\Incident\Repositories\IncidentRepositoryInterface;
use App\Modules\Incident\Repositories\IncidentRepository;

// Document
use App\Modules\Document\Repositories\DocumentRepositoryInterface;
use App\Modules\Document\Repositories\DocumentRepository;

// Checklist
use App\Modules\Checklist\Repositories\ChecklistRepositoryInterface;
use App\Modules\Checklist\Repositories\ChecklistRepository;

// Maintenance
use App\Modules\Maintenance\Repositories\MaintenanceRepositoryInterface;
use App\Modules\Maintenance\Repositories\MaintenanceRepository;

// Pricing
use App\Modules\Pricing\Repositories\PlanRepositoryInterface;
use App\Modules\Pricing\Repositories\PlanRepository;

// Events & Listeners
use App\Modules\Incident\Events\IncidentReportedEvent;
use App\Modules\Expense\Events\ExpenseCreatedEvent;
use App\Modules\Mission\Events\MissionStartedEvent;
use App\Modules\Notification\Listeners\NotifyManagerOnIncidentListener;
use App\Modules\Notification\Listeners\NotifyOnExpenseThresholdListener;
use App\Modules\Notification\Listeners\NotifyDriverOnMissionAssignedListener;

// Jobs
use App\Jobs\CheckDocumentExpiryJob;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyRepositoryInterface::class,  CompanyRepository::class);
        $this->app->bind(UserRepositoryInterface::class,     UserRepository::class);
        $this->app->bind(VehicleRepositoryInterface::class,  VehicleRepository::class);
        $this->app->bind(DriverRepositoryInterface::class,   DriverRepository::class);
        $this->app->bind(MissionRepositoryInterface::class,  MissionRepository::class);
        $this->app->bind(ExpenseRepositoryInterface::class,  ExpenseRepository::class);
        $this->app->bind(IncidentRepositoryInterface::class, IncidentRepository::class);
        $this->app->bind(DocumentRepositoryInterface::class, DocumentRepository::class);
        $this->app->bind(ChecklistRepositoryInterface::class, ChecklistRepository::class);
        $this->app->bind(MaintenanceRepositoryInterface::class, MaintenanceRepository::class);
        $this->app->bind(PlanRepositoryInterface::class, PlanRepository::class);
    }

    public function boot(): void
    {
        // M7 — Incident → notif gestionnaire
        Event::listen(IncidentReportedEvent::class,  NotifyManagerOnIncidentListener::class);

        // M6 — Dépense > seuil → notif gestionnaire
        Event::listen(ExpenseCreatedEvent::class,    NotifyOnExpenseThresholdListener::class);

        // M4 — Mission démarrée → notif chauffeur
        Event::listen(MissionStartedEvent::class,    NotifyDriverOnMissionAssignedListener::class);

        // M8 — Cron quotidien pour les alertes d'expiration de documents
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->job(new CheckDocumentExpiryJob)->dailyAt('07:00');
        });
    }
}
