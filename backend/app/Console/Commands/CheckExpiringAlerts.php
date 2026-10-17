<?php

namespace App\Console\Commands;

use App\Modules\Driver\Models\Driver;
use App\Modules\Maintenance\Models\MaintenanceSchedule;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiringAlerts extends Command
{
    
    protected $signature = 'alerts:check-expiring';
    protected $description = 'Check for expiring driver licenses and overdue maintenance';

    public function handle(NotificationService $notificationService): int
    {
        $this->info('Checking expiring alerts...');

        // ── M3: Driver License Expiry (30 days) ──────────────
        $expiringDrivers = Driver::whereNotNull('license_expiry')
            ->where('license_expiry', '>', now())
            ->where('license_expiry', '<=', now()->addDays(30))
            ->where('status', '!=', 'inactive')
            ->get();

        foreach ($expiringDrivers as $driver) {
            $daysLeft = now()->diffInDays($driver->license_expiry, false);
            $daysLeft = max(0, (int) $daysLeft);

            // Only alert at J-30, J-7, J-1
            if (!in_array($daysLeft, [30, 7, 1])) continue;

            $notificationService->create([
                'user_id'    => $driver->user_id,
                'company_id' => $driver->company_id,
                'type'       => 'license_expiring',
                'title'      => "Permis expirant dans {$daysLeft} jour(s)",
                'body'       => "Le permis du chauffeur {$driver->license_number} expire le {$driver->license_expiry->format('d/m/Y')}.",
                'channel'    => 'dashboard',
            ]);

            Log::info('License expiry alert sent', ['driver_id' => $driver->id, 'days_left' => $daysLeft]);
        }

        $this->info("Checked {$expiringDrivers->count()} expiring licenses.");

        // ── Maintenance Overdue Alerts ────────────────────────
        $overdueSchedules = MaintenanceSchedule::with('vehicle')
            ->where('status', '!=', 'completed')
            ->where(function ($q) {
                $q->where('next_due_date', '<=', now())
                  ->orWhereRaw('next_due_km <= (SELECT mileage FROM vehicles WHERE vehicles.id = maintenance_schedules.vehicle_id)');
            })
            ->get();

        foreach ($overdueSchedules as $schedule) {
            if ($schedule->status !== 'overdue') {
                $schedule->update(['status' => 'overdue']);
            }

            // Notify company admins
            $admins = \App\Modules\User\Models\User::where('company_id', $schedule->company_id)
                ->role('admin')
                ->get();

            foreach ($admins as $admin) {
                $plateNumber = $schedule->vehicle?->plate_number ?? 'N/A';
                $maintenanceType = $schedule->maintenance_type ?? $schedule->type ?? 'N/A';
                $notificationService->create([
                    'user_id'    => $admin->id,
                    'company_id' => $schedule->company_id,
                    'type'       => 'maintenance_due',
                    'title'      => "Maintenance en retard: {$plateNumber}",
                    'body'       => "La maintenance '{$maintenanceType}' est en retard.",
                    'channel'    => 'dashboard',
                ]);
            }
        }

        $this->info("Checked {$overdueSchedules->count()} overdue maintenance schedules.");
        return Command::SUCCESS;
    }
}
