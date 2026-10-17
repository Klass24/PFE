<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Modules\User\Models\User;
use App\Modules\Company\Models\Company;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'demo-company')->firstOrFail();

        // Super Admin SaaS — pas lié à une entreprise spécifique
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@fleetcontrol.app'],
            [
                'uuid'       => Str::uuid(),
                'company_id' => $company->id,
                'name'       => 'Super Admin',
                'password'   => Hash::make('password'),
                'is_active'  => true,
            ]
        );
        $superAdmin->assignRole('super_admin');

        $admin = User::firstOrCreate(
            ['email' => 'admin@fleetcontrol.app'],
            [
                'uuid'       => Str::uuid(),
                'company_id' => $company->id,
                'name'       => 'Admin FleetControl',
                'password'   => Hash::make('password'),
                'is_active'  => true,
            ]
        );
        $admin->assignRole('admin');

        $manager = User::firstOrCreate(
            ['email' => 'manager@fleetcontrol.app'],
            [
                'uuid'       => Str::uuid(),
                'company_id' => $company->id,
                'name'       => 'Manager Demo',
                'password'   => Hash::make('password'),
                'is_active'  => true,
            ]
        );
        $manager->assignRole('manager');

        $driver = User::firstOrCreate(
            ['email' => 'driver@fleetcontrol.app'],
            [
                'uuid'       => Str::uuid(),
                'company_id' => $company->id,
                'name'       => 'Chauffeur Demo',
                'password'   => Hash::make('password'),
                'is_active'  => true,
            ]
        );
        $driver->assignRole('driver');
    }
}
