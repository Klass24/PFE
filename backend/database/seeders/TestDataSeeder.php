<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Vehicle\Models\Vehicle;
use App\Modules\Driver\Models\Driver;
use App\Modules\Mission\Models\Mission;
use App\Modules\Expense\Models\Expense;
use App\Modules\Incident\Models\Incident;
use App\Modules\Document\Models\Document;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'demo-company')->first();
        if (!$company) {
            $this->command->error('Company not found. Run CompanySeeder first.');
            return;
        }

        $this->command->info('Seeding vehicles...');
        $vehicles = $this->seedVehicles($company->id);

        $this->command->info('Seeding drivers...');
        $drivers = $this->seedDrivers($company->id);

        $this->command->info('Seeding missions...');
        $missions = $this->seedMissions($company->id, $vehicles, $drivers);

        $this->command->info('Seeding expenses...');
        $this->seedExpenses($company->id, $missions);

        $this->command->info('Seeding incidents...');
        $this->seedIncidents($company->id, $missions);

        $this->command->info('Seeding documents...');
        $this->seedDocuments($company->id, $vehicles, $drivers);

        $this->command->info('✓ Test data seeded successfully!');
    }

    // ── Vehicles ──────────────────────────────────────────────────────────────
    private function seedVehicles(int $companyId): array
    {
        $data = [
            ['brand' => 'Renault', 'model' => 'Kangoo',   'plate' => '12-AB-34', 'year' => 2021, 'fuel' => 'diesel',   'mileage' => 45200, 'status' => 'available'],
            ['brand' => 'Peugeot', 'model' => 'Expert',   'plate' => '56-CD-78', 'year' => 2020, 'fuel' => 'diesel',   'mileage' => 78300, 'status' => 'available'],
            ['brand' => 'Ford',    'model' => 'Transit',  'plate' => '90-EF-12', 'year' => 2022, 'fuel' => 'diesel',   'mileage' => 32100, 'status' => 'on_mission'],
            ['brand' => 'Toyota',  'model' => 'Proace',   'plate' => '34-GH-56', 'year' => 2019, 'fuel' => 'diesel',   'mileage' => 120500,'status' => 'maintenance'],
            ['brand' => 'Citroën', 'model' => 'Berlingo', 'plate' => '78-IJ-90', 'year' => 2023, 'fuel' => 'electric', 'mileage' => 15800, 'status' => 'available'],
            ['brand' => 'Mercedes','model' => 'Sprinter', 'plate' => '11-KL-22', 'year' => 2021, 'fuel' => 'diesel',   'mileage' => 67400, 'status' => 'available'],
            ['brand' => 'Renault', 'model' => 'Master',   'plate' => '33-MN-44', 'year' => 2020, 'fuel' => 'diesel',   'mileage' => 95600, 'status' => 'on_mission'],
            ['brand' => 'Fiat',    'model' => 'Ducato',   'plate' => '55-OP-66', 'year' => 2022, 'fuel' => 'diesel',   'mileage' => 41200, 'status' => 'available'],
        ];

        $vehicles = [];
        foreach ($data as $d) {
            $vehicles[] = Vehicle::firstOrCreate(
                ['plate_number' => $d['plate'], 'company_id' => $companyId],
                [
                    'uuid'       => Str::uuid(),
                    'company_id' => $companyId,
                    'brand'      => $d['brand'],
                    'model'      => $d['model'],
                    'year'       => $d['year'],
                    'fuel_type'  => $d['fuel'],
                    'mileage'    => $d['mileage'],
                    'status'     => $d['status'],
                ]
            );
        }
        return $vehicles;
    }

    // ── Drivers ───────────────────────────────────────────────────────────────
    private function seedDrivers(int $companyId): array
    {
        $driverUsers = [
            ['name' => 'Karim Benzara',  'email' => 'karim.benzara@demo.com',  'phone' => '+33 6 11 22 33 44', 'license' => 'DRV-2019-0011', 'expiry' => '2027-06-15'],
            ['name' => 'Sara Mansouri',  'email' => 'sara.mansouri@demo.com',  'phone' => '+33 6 22 33 44 55', 'license' => 'DRV-2020-0042', 'expiry' => '2026-09-20'],
            ['name' => 'Ahmed Ouali',    'email' => 'ahmed.ouali@demo.com',    'phone' => '+33 6 33 44 55 66', 'license' => 'DRV-2018-0073', 'expiry' => '2025-12-01'],
            ['name' => 'Nadia Ferhat',   'email' => 'nadia.ferhat@demo.com',   'phone' => '+33 6 44 55 66 77', 'license' => 'DRV-2021-0089', 'expiry' => '2028-03-10'],
            ['name' => 'Youssef Talbi',  'email' => 'youssef.talbi@demo.com',  'phone' => '+33 6 55 66 77 88', 'license' => 'DRV-2022-0105', 'expiry' => '2029-08-25'],
        ];

        $drivers = [];
        foreach ($driverUsers as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'uuid'       => Str::uuid(),
                    'company_id' => $companyId,
                    'name'       => $d['name'],
                    'password'   => bcrypt('password'),
                    'is_active'  => true,
                ]
            );
            $user->assignRole('driver');

            $drivers[] = Driver::firstOrCreate(
                ['user_id' => $user->id, 'company_id' => $companyId],
                [
                    'uuid'            => Str::uuid(),
                    'company_id'      => $companyId,
                    'user_id'         => $user->id,
                    'license_number'  => $d['license'],
                    'license_expiry'  => $d['expiry'],
                    'phone'           => $d['phone'],
                    'status'          => 'available',
                ]
            );
        }
        return $drivers;
    }

    // ── Missions ──────────────────────────────────────────────────────────────
    private function seedMissions(int $companyId, array $vehicles, array $drivers): array
    {
        $adminUser = User::where('company_id', $companyId)->whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();

        $data = [
            [
                'origin' => 'Paris',       'destination' => 'Lyon',
                'start'  => '-15 days',    'end' => '-12 days',
                'status' => 'completed',   'vi' => 0, 'di' => 0,
                'notes'  => 'Livraison matériel informatique.',
            ],
            [
                'origin' => 'Lyon',        'destination' => 'Marseille',
                'start'  => '-10 days',    'end' => '-8 days',
                'status' => 'completed',   'vi' => 1, 'di' => 1,
                'notes'  => 'Transport de marchandises fragiles.',
            ],
            [
                'origin' => 'Marseille',   'destination' => 'Nice',
                'start'  => '-5 days',     'end' => '-3 days',
                'status' => 'completed',   'vi' => 2, 'di' => 2,
                'notes'  => 'Collecte clients VIP.',
            ],
            [
                'origin' => 'Paris',       'destination' => 'Bordeaux',
                'start'  => '-2 days',     'end' => '+1 day',
                'status' => 'in_progress', 'vi' => 6, 'di' => 3,
                'notes'  => 'Livraison urgente.',
            ],
            [
                'origin' => 'Bordeaux',    'destination' => 'Toulouse',
                'start'  => '+1 day',      'end' => '+3 days',
                'status' => 'in_progress', 'vi' => 2, 'di' => 4,
                'notes'  => 'Mission logistique événementielle.',
            ],
            [
                'origin' => 'Paris',       'destination' => 'Nantes',
                'start'  => '+3 days',     'end' => '+5 days',
                'status' => 'planned',     'vi' => 4, 'di' => 0,
                'notes'  => 'Déplacement commercial.',
            ],
            [
                'origin' => 'Strasbourg',  'destination' => 'Paris',
                'start'  => '+5 days',     'end' => '+7 days',
                'status' => 'planned',     'vi' => 5, 'di' => 1,
                'notes'  => 'Retour matériel foire.',
            ],
            [
                'origin' => 'Paris',       'destination' => 'Lille',
                'start'  => '-20 days',    'end' => '-17 days',
                'status' => 'completed',   'vi' => 7, 'di' => 2,
                'notes'  => 'Transport archives.',
            ],
            [
                'origin' => 'Lille',       'destination' => 'Paris',
                'start'  => '-8 days',     'end' => '-6 days',
                'status' => 'completed',   'vi' => 3, 'di' => 3,
                'notes'  => 'Récupération équipement.',
            ],
            [
                'origin' => 'Paris',       'destination' => 'Tours',
                'start'  => '-1 day',      'end' => '+2 days',
                'status' => 'cancelled',   'vi' => 1, 'di' => 4,
                'notes'  => 'Mission annulée — client indisponible.',
            ],
        ];

        $missions = [];
        foreach ($data as $d) {
            $vehicle = $vehicles[$d['vi'] % count($vehicles)];
            $driver  = $drivers[$d['di'] % count($drivers)];

            $missions[] = Mission::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'origin'     => $d['origin'],
                    'destination'=> $d['destination'],
                    'start_date' => now()->modify($d['start'])->format('Y-m-d'),
                ],
                [
                    'uuid'        => Str::uuid(),
                    'company_id'  => $companyId,
                    'vehicle_id'  => $vehicle->id,
                    'driver_id'   => $driver->id,
                    'end_date'    => now()->modify($d['end'])->format('Y-m-d'),
                    'status'      => $d['status'],
                    'notes'       => $d['notes'],
                ]
            );
        }
        return $missions;
    }

    // ── Expenses ──────────────────────────────────────────────────────────────
    private function seedExpenses(int $companyId, array $missions): void
    {
        $adminUser = User::where('company_id', $companyId)->whereHas('roles', fn($q) => $q->where('name', 'admin'))->first();

        $expenseData = [
            ['type' => 'fuel',   'amount' => 85.50,  'liters' => 57.0,  'notes' => 'Plein carburant autoroute A6'],
            ['type' => 'toll',   'amount' => 32.80,  'liters' => null,  'notes' => 'Péage Paris-Lyon'],
            ['type' => 'fuel',   'amount' => 62.00,  'liters' => 41.3,  'notes' => 'Plein station Total'],
            ['type' => 'repair', 'amount' => 180.00, 'liters' => null,  'notes' => 'Remplacement pneu avant droit'],
            ['type' => 'toll',   'amount' => 18.40,  'liters' => null,  'notes' => 'Péage A7 Marseille'],
            ['type' => 'fuel',   'amount' => 75.20,  'liters' => 50.1,  'notes' => 'Plein BP autoroute'],
            ['type' => 'other',  'amount' => 45.00,  'liters' => null,  'notes' => 'Parking longue durée'],
            ['type' => 'fuel',   'amount' => 90.00,  'liters' => 60.0,  'notes' => 'Plein complet avant départ'],
            ['type' => 'toll',   'amount' => 24.60,  'liters' => null,  'notes' => 'Péage A10 Bordeaux'],
            ['type' => 'repair', 'amount' => 320.00, 'liters' => null,  'notes' => 'Vidange + filtres'],
            ['type' => 'fuel',   'amount' => 55.80,  'liters' => 37.2,  'notes' => 'Plein partiel station routière'],
            ['type' => 'other',  'amount' => 28.50,  'liters' => null,  'notes' => 'Lavage véhicule'],
            ['type' => 'toll',   'amount' => 41.20,  'liters' => null,  'notes' => 'Péage A1 nord'],
            ['type' => 'fuel',   'amount' => 78.40,  'liters' => 52.3,  'notes' => 'Plein Leclerc autoroute'],
            ['type' => 'repair', 'amount' => 95.00,  'liters' => null,  'notes' => 'Remplacement ampoule + révision'],
        ];

        foreach ($missions as $i => $mission) {
            // 1 à 3 dépenses par mission
            $count = ($i % 3) + 1;
            for ($j = 0; $j < $count; $j++) {
                $exp = $expenseData[($i * 3 + $j) % count($expenseData)];
                // Éviter les doublons par uuid unique
                Expense::create([
                    'uuid'       => Str::uuid(),
                    'company_id' => $companyId,
                    'mission_id' => $mission->id,
                    'type'       => $exp['type'],
                    'amount'     => $exp['amount'],
                    'liters'     => $exp['liters'],
                    'notes'      => $exp['notes'],
                    'created_by' => $adminUser?->id,
                ]);
            }
        }
    }

    // ── Incidents ─────────────────────────────────────────────────────────────
    private function seedIncidents(int $companyId, array $missions): void
    {
        $incidentData = [
            ['type' => 'accident',   'desc' => 'Accrochage mineur en parking — dommages légers sur le pare-choc avant.',   'lat' => 45.7640,  'lng' => 4.8357],
            ['type' => 'breakdown',  'desc' => 'Panne moteur sur autoroute A7 — véhicule remorqué.',                       'lat' => 43.2965,  'lng' => 5.3698],
            ['type' => 'flat_tire',  'desc' => 'Crevaison pneu arrière gauche sur national.',                               'lat' => 48.8566,  'lng' => 2.3522],
            ['type' => 'other',      'desc' => 'Radar automatique déclenché à 112 km/h zone 90.',                          'lat' => 47.3900,  'lng' => 0.6892],
        ];

        // Ajouter incidents uniquement sur les missions complétées ou en cours
        $eligibleMissions = array_filter($missions, fn($m) => in_array($m->status, ['completed', 'in_progress']));

        foreach (array_slice(array_values($eligibleMissions), 0, 4) as $i => $mission) {
            $inc = $incidentData[$i % count($incidentData)];
            Incident::firstOrCreate(
                ['mission_id' => $mission->id, 'company_id' => $companyId, 'type' => $inc['type']],
                [
                    'uuid'        => Str::uuid(),
                    'company_id'  => $companyId,
                    'mission_id'  => $mission->id,
                    'type'        => $inc['type'],
                    'description' => $inc['desc'],
                    'latitude'    => $inc['lat'],
                    'longitude'   => $inc['lng'],
                ]
            );
        }
    }

    // ── Documents ─────────────────────────────────────────────────────────────
    private function seedDocuments(int $companyId, array $vehicles, array $drivers): void
    {
        $now = now();

        // Documents véhicules
        $vehicleDocs = [
            ['type' => 'insurance',        'expiry' => '+8 months', 'name' => 'Assurance tous risques'],
            ['type' => 'registration',     'expiry' => '+2 years',  'name' => 'Carte grise'],
            ['type' => 'technical_control','expiry' => '+6 months', 'name' => 'Contrôle technique'],
            ['type' => 'insurance',        'expiry' => '+1 month',  'name' => 'Assurance — expire bientôt'],
            ['type' => 'technical_control','expiry' => '-5 days',   'name' => 'CT expiré — à renouveler'],
        ];

        foreach (array_slice($vehicles, 0, 5) as $i => $vehicle) {
            $doc = $vehicleDocs[$i];
            Document::firstOrCreate(
                ['documentable_id' => $vehicle->id, 'documentable_type' => Vehicle::class, 'type' => $doc['type'], 'company_id' => $companyId],
                [
                    'uuid'              => Str::uuid(),
                    'company_id'        => $companyId,
                    'documentable_type' => Vehicle::class,
                    'documentable_id'   => $vehicle->id,
                    'type'              => $doc['type'],
                    'file_path'         => 'documents/placeholder.pdf',
                    'expiry_date'       => $now->copy()->modify($doc['expiry'])->format('Y-m-d'),
                ]
            );
        }

        // Documents chauffeurs
        $driverDocs = [
            ['type' => 'license', 'expiry' => '+2 years',  'name' => 'Permis de conduire'],
            ['type' => 'other',   'expiry' => '+1 year',   'name' => 'Visite médicale'],
            ['type' => 'license', 'expiry' => '+3 months', 'name' => 'Permis — bientôt expiré'],
        ];

        foreach (array_slice($drivers, 0, 3) as $i => $driver) {
            $doc = $driverDocs[$i];
            Document::firstOrCreate(
                ['documentable_id' => $driver->id, 'documentable_type' => Driver::class, 'type' => $doc['type'], 'company_id' => $companyId],
                [
                    'uuid'              => Str::uuid(),
                    'company_id'        => $companyId,
                    'documentable_type' => Driver::class,
                    'documentable_id'   => $driver->id,
                    'type'              => $doc['type'],
                    'file_path'         => 'documents/placeholder.pdf',
                    'expiry_date'       => $now->copy()->modify($doc['expiry'])->format('Y-m-d'),
                ]
            );
        }
    }
}
