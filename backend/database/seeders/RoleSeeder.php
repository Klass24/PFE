<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Vehicles
            'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.delete',
            // Drivers
            'drivers.view', 'drivers.create', 'drivers.update', 'drivers.delete',
            // Missions
            'missions.view', 'missions.create', 'missions.update', 'missions.delete',
            'missions.start', 'missions.complete', 'missions.cancel',
            // Expenses
            'expenses.view', 'expenses.create', 'expenses.update', 'expenses.delete',
            // Incidents
            'incidents.view', 'incidents.create', 'incidents.update', 'incidents.delete',
            // Documents
            'documents.view', 'documents.create', 'documents.update', 'documents.delete',
            // Users
            'users.view', 'users.create', 'users.update', 'users.delete',
            // Company
            'company.manage',
            // Reports
            'reports.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'sanctum']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $admin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'sanctum']);
        $manager->syncPermissions([
            'vehicles.view', 'vehicles.create', 'vehicles.update',
            'drivers.view', 'drivers.create', 'drivers.update',
            'missions.view', 'missions.create', 'missions.update', 'missions.start', 'missions.complete', 'missions.cancel',
            'expenses.view', 'expenses.create', 'expenses.update',
            'incidents.view', 'incidents.create', 'incidents.update',
            'documents.view', 'documents.create', 'documents.update',
            'reports.view',
        ]);

        $driver = Role::firstOrCreate(['name' => 'driver', 'guard_name' => 'sanctum']);
        $driver->syncPermissions([
            'missions.view',
            'expenses.view', 'expenses.create',
            'incidents.view', 'incidents.create',
        ]);

        // Super Admin — accès global cross-tenant (back-office SaaS)
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        $superAdmin->syncPermissions(Permission::all());
    }
}
