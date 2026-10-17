<?php

namespace App\Modules\Driver\Services;

use App\Core\Services\BaseService;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Repositories\DriverRepositoryInterface;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DriverService extends BaseService
{
    public function __construct(
        private DriverRepositoryInterface $repo,
        private NotificationService       $notificationService,
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->repo->paginate($filters);
    }

    public function create(array $data): Driver
    {
        $user = auth()->user();
        $data['company_id'] = $user->company_id;

        $driverPermissions = (array) ($data['permissions'] ?? []);
        unset($data['permissions']);

        return DB::transaction(function () use ($user, $data, $driverPermissions) {
            $company = $user->company;

            if (empty($data['user_id'])) {
                $userData = (array) ($data['user'] ?? []);
                unset($data['user']);

                // Vérification quota utilisateurs si on crée un compte lié au chauffeur.
                $usersCount = User::where('company_id', $company->id)->count();
                if ($usersCount >= $company->max_users) {
                    throw ValidationException::withMessages([
                        'company' => "Quota atteint : votre plan permet {$company->max_users} utilisateur(s) maximum.",
                    ]);
                }

                $driverUser = User::create([
                    'company_id' => $company->id,
                    'name' => (string) ($userData['name'] ?? ''),
                    'email' => (string) ($userData['email'] ?? ''),
                    'password' => Hash::make((string) ($userData['password'] ?? '')),
                    'is_active' => array_key_exists('is_active', $userData)
                        ? (bool) $userData['is_active']
                        : true,
                ]);
                $driverUser->syncRoles(['driver']);

                if (!empty($driverPermissions)) {
                    if (!$user->hasAnyRole(['admin', 'super_admin'])) {
                        throw ValidationException::withMessages([
                            'permissions' => 'Seul un administrateur peut définir des permissions personnalisées.',
                        ]);
                    }
                    $driverUser->syncPermissions($driverPermissions);
                }

                $data['user_id'] = $driverUser->id;
            } else {
                $driverUser = User::where('id', $data['user_id'])
                    ->where('company_id', $company->id)
                    ->firstOrFail();
                if (!$driverUser->hasRole('driver')) {
                    $driverUser->assignRole('driver');
                }
                if (!empty($driverPermissions) && $user->hasAnyRole(['admin', 'super_admin'])) {
                    $driverUser->syncPermissions($driverPermissions);
                }
            }

            // M1 — Vérification quota plan chauffeurs
            $currentCount = Driver::count(); // scoped par company
            if ($currentCount >= $company->max_drivers) {
                throw ValidationException::withMessages([
                    'company' => "Quota atteint : votre plan permet {$company->max_drivers} chauffeur(s) maximum.",
                ]);
            }

            $driver = $this->repo->create($data);

            // M3 — Alerte immédiate si permis déjà expiré ou expirant dans 30j
            $this->checkLicenseExpiry($driver);

            Log::info('Driver created', ['driver_id' => $driver->id, 'user' => auth()->id()]);
            return $driver;
        });
    }

    public function update(Driver $driver, array $data): Driver
    {
        $driver = $this->repo->update($driver, $data);

        if (isset($data['license_expiry'])) {
            $this->checkLicenseExpiry($driver);
        }

        Log::info('Driver updated', ['driver_id' => $driver->id]);
        return $driver;
    }

    public function delete(Driver $driver): void
    {
        $this->repo->delete($driver);
        Log::info('Driver deleted', ['driver_id' => $driver->id]);
    }

    // -------------------------------------------------------------------------
    //  Règles métier privées
    // -------------------------------------------------------------------------

    /**
     * M3 — Envoie une alerte si le permis expire dans moins de 30 jours.
     */
    private function checkLicenseExpiry(Driver $driver): void
    {
        $expiry = $driver->license_expiry;

        if (!$expiry) {
            return;
        }

        $daysUntilExpiry = now()->diffInDays($expiry, false);

        if ($daysUntilExpiry < 0) {
            $message = "Le permis du chauffeur {$driver->user?->name} (n° {$driver->license_number}) est expiré depuis le {$expiry->format('d/m/Y')}.";
            $this->notificationService->notifyManagers($driver->company_id, 'license_expired', $message);
            Log::warning('Driver license expired', ['driver_id' => $driver->id]);
        } elseif ($daysUntilExpiry <= 30) {
            $message = "Le permis du chauffeur {$driver->user?->name} (n° {$driver->license_number}) expire dans {$daysUntilExpiry} jour(s) (le {$expiry->format('d/m/Y')}).";
            $this->notificationService->notifyManagers($driver->company_id, 'license_expiring', $message);
            Log::info('Driver license expiring soon', ['driver_id' => $driver->id, 'days' => $daysUntilExpiry]);
        }
    }
}
