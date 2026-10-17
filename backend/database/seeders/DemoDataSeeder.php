<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Driver\Models\Driver;
use App\Modules\Expense\Models\Expense;
use App\Modules\Incident\Models\Incident;
use App\Modules\Mission\Models\Mission;
use App\Modules\User\Models\User;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'demo-company')->firstOrFail();

        // ── Vehicles ──────────────────────────────────────────────────────
        $vehicles = [];
        $vehicleData = [
            ['plate' => 'CG-101-AA', 'brand' => 'Toyota',   'model' => 'Hilux',     'type' => 'truck',  'fuel' => 'diesel',   'year' => 2023, 'mileage' => 45000],
            ['plate' => 'CG-102-BB', 'brand' => 'Renault',  'model' => 'Master',    'type' => 'van',    'fuel' => 'diesel',   'year' => 2022, 'mileage' => 78000],
            ['plate' => 'CG-103-CC', 'brand' => 'Hyundai',  'model' => 'HD78',      'type' => 'truck',  'fuel' => 'diesel',   'year' => 2024, 'mileage' => 12000],
            ['plate' => 'CG-104-DD', 'brand' => 'Peugeot',  'model' => 'Partner',   'type' => 'van',    'fuel' => 'diesel',   'year' => 2021, 'mileage' => 95000],
            ['plate' => 'CG-105-EE', 'brand' => 'Mercedes', 'model' => 'Sprinter',  'type' => 'van',    'fuel' => 'diesel',   'year' => 2023, 'mileage' => 33000],
        ];

        foreach ($vehicleData as $v) {
            $vehicles[] = Vehicle::firstOrCreate(['plate_number' => $v['plate']], [
                'uuid'         => Str::uuid(),
                'company_id'   => $company->id,
                'plate_number' => $v['plate'],
                'brand'        => $v['brand'],
                'model'        => $v['model'],
                'type'         => $v['type'],
                'fuel_type'    => $v['fuel'],
                'year'         => $v['year'],
                'mileage'      => $v['mileage'],
                'status'       => 'available',
            ]);
        }

        // ── Additional driver users ───────────────────────────────────────
        $driverUsers = [];
        $driverData = [
            ['name' => 'Karim Bencherif', 'email' => 'karim@fleetcontrol.app', 'license' => 'CG-B-2020-4455', 'phone' => '+242061000001'],
            ['name' => 'Yacine Mesbah',   'email' => 'yacine@fleetcontrol.app', 'license' => 'CG-B-2019-7788', 'phone' => '+242061000002'],
            ['name' => 'Farid Hamdane',   'email' => 'farid@fleetcontrol.app',  'license' => 'CG-C-2021-1122', 'phone' => '+242061000003'],
        ];

        foreach ($driverData as $d) {
            $user = User::firstOrCreate(['email' => $d['email']], [
                'uuid'       => Str::uuid(),
                'company_id' => $company->id,
                'name'       => $d['name'],
                'password'   => Hash::make('password'),
                'is_active'  => true,
            ]);
            $user->assignRole('driver');

            $driverUsers[] = Driver::firstOrCreate(['user_id' => $user->id], [
                'uuid'           => Str::uuid(),
                'company_id'     => $company->id,
                'user_id'        => $user->id,
                'license_number' => $d['license'],
                'license_expiry' => now()->addMonths(rand(2, 24)),
                'phone'          => $d['phone'],
                'status'         => 'available',
            ]);
        }

        // ── Missions ──────────────────────────────────────────────────────
        $routes = [
            ['Brazzaville', 'Pointe-Noire'],
            ['Brazzaville', 'Dolisie'],
            ['Pointe-Noire', 'Nkayi'],
            ['Brazzaville', 'Owando'],
            ['Dolisie', 'Ouesso'],
            ['Brazzaville', 'Oyo'],
        ];

        $statuses = ['planned', 'in_progress', 'completed', 'completed', 'completed', 'planned'];

        foreach ($routes as $i => [$origin, $dest]) {
            $vehicle = $vehicles[$i % count($vehicles)];
            $driver  = $driverUsers[$i % count($driverUsers)];
            $status  = $statuses[$i];

            $mission = Mission::firstOrCreate(
                ['origin' => $origin, 'destination' => $dest, 'company_id' => $company->id, 'driver_id' => $driver->id],
                [
                    'uuid'              => Str::uuid(),
                    'company_id'        => $company->id,
                    'vehicle_id'        => $vehicle->id,
                    'driver_id'         => $driver->id,
                    'origin'            => $origin,
                    'destination'       => $dest,
                    'planned_departure' => now()->subDays(10 - $i),
                    'planned_arrival'   => now()->subDays(10 - $i)->addHours(rand(3, 8)),
                    'status'            => $status,
                    'start_date'        => $status !== 'planned' ? now()->subDays(10 - $i) : null,
                    'end_date'          => $status === 'completed' ? now()->subDays(10 - $i)->addHours(rand(3, 8)) : null,
                ]
            );

            // Expenses for completed/in_progress missions
            if (in_array($status, ['completed', 'in_progress'])) {
                Expense::firstOrCreate(['mission_id' => $mission->id, 'type' => 'fuel', 'company_id' => $company->id], [
                    'uuid'       => Str::uuid(),
                    'company_id' => $company->id,
                    'mission_id' => $mission->id,
                    'type'       => 'fuel',
                    'amount'     => rand(3000, 8000),
                    'liters'     => rand(30, 70),
                    'mileage'    => $vehicle->mileage + rand(100, 500),
                    'created_by' => $driver->user_id,
                ]);

                if (rand(0, 1)) {
                    Expense::firstOrCreate(['mission_id' => $mission->id, 'type' => 'toll', 'company_id' => $company->id], [
                        'uuid'       => Str::uuid(),
                        'company_id' => $company->id,
                        'mission_id' => $mission->id,
                        'type'       => 'toll',
                        'amount'     => rand(200, 800),
                        'created_by' => $driver->user_id,
                    ]);
                }
            }

            // Incidents (randomly on some missions)
            if ($i === 2) {
                Incident::firstOrCreate(['mission_id' => $mission->id, 'company_id' => $company->id], [
                    'uuid'        => Str::uuid(),
                    'company_id'  => $company->id,
                    'mission_id'  => $mission->id,
                    'type'        => 'breakdown',
                    'description' => 'Surchauffe moteur à la sortie de Dolisie, arrêt 45 minutes.',
                    'latitude'    => -4.1983,
                    'longitude'   => 12.6666,
                    'reported_at' => now()->subDays(7),
                ]);
            }
        }

        $this->command->info('  ✔ Demo data seeded: 5 vehicles, 3 drivers, 6 missions, expenses & incidents');
    }
}
