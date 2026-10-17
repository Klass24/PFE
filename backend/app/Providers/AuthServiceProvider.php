<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Modules\Vehicle\Models\Vehicle::class       => \App\Modules\Vehicle\Policies\VehiclePolicy::class,
        \App\Modules\Driver\Models\Driver::class         => \App\Modules\Driver\Policies\DriverPolicy::class,
        \App\Modules\Mission\Models\Mission::class       => \App\Modules\Mission\Policies\MissionPolicy::class,
        \App\Modules\Expense\Models\Expense::class       => \App\Modules\Expense\Policies\ExpensePolicy::class,
        \App\Modules\User\Models\User::class             => \App\Modules\User\Policies\UserPolicy::class,
        \App\Modules\Incident\Models\Incident::class     => \App\Modules\Incident\Policies\IncidentPolicy::class,
        \App\Modules\Document\Models\Document::class     => \App\Modules\Document\Policies\DocumentPolicy::class,
        \App\Modules\Checklist\Models\Checklist::class   => \App\Modules\Checklist\Policies\ChecklistPolicy::class,
        \App\Modules\Notification\Models\Notification::class => \App\Modules\Notification\Policies\NotificationPolicy::class,
        \App\Modules\Maintenance\Models\MaintenanceSchedule::class => \App\Modules\Maintenance\Policies\MaintenancePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Les admins et super_admins passent toutes les policies
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}
