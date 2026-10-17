<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'uuid'          => Str::uuid(),
                'name'          => 'Starter',
                'slug'          => 'starter',
                'price_monthly' => 0,
                'price_yearly'  => 0,
                'currency'      => 'EUR',
                'max_vehicles'  => 5,
                'max_drivers'   => 5,
                'max_users'     => 3,
                'features'      => json_encode([
                    'Dashboard de base',
                    'Gestion véhicules & chauffeurs',
                    'Suivi des missions',
                    'Support email',
                ]),
                'is_active'   => true,
                'is_popular'  => false,
                'sort_order'  => 1,
                'trial_days'  => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'uuid'          => Str::uuid(),
                'name'          => 'Business',
                'slug'          => 'pro',
                'price_monthly' => 49.00,
                'price_yearly'  => 468.00,
                'currency'      => 'EUR',
                'max_vehicles'  => 20,
                'max_drivers'   => 20,
                'max_users'     => 10,
                'features'      => json_encode([
                    'Dashboard complet',
                    'Gestion véhicules & chauffeurs',
                    'Suivi des missions',
                    'Dépenses & incidents',
                    'Documents avec alertes',
                    'Export CSV',
                    'Notifications email',
                    'Maintenance préventive',
                    'Suivi carburant',
                    'Support prioritaire',
                ]),
                'is_active'   => true,
                'is_popular'  => true,
                'sort_order'  => 2,
                'trial_days'  => 14,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'uuid'          => Str::uuid(),
                'name'          => 'Enterprise',
                'slug'          => 'enterprise',
                'price_monthly' => 149.00,
                'price_yearly'  => 1428.00,
                'currency'      => 'EUR',
                'max_vehicles'  => 999,
                'max_drivers'   => 999,
                'max_users'     => 999,
                'features'      => json_encode([
                    'Tout Business inclus',
                    'Véhicules illimités',
                    'Chauffeurs illimités',
                    'Utilisateurs illimités',
                    'GPS Tracking temps réel',
                    'Géofencing',
                    'Règles d\'alerte avancées',
                    'API & Intégrations',
                    'Notifications WhatsApp',
                    'Support dédié',
                    'SLA garanti',
                ]),
                'is_active'   => true,
                'is_popular'  => false,
                'sort_order'  => 3,
                'trial_days'  => 14,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->updateOrInsert(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        // Lier les companies existantes à leur plan
        $allPlans = DB::table('plans')->pluck('id', 'slug');
        foreach ($allPlans as $slug => $planId) {
            DB::table('companies')
                ->where('plan', $slug)
                ->whereNull('plan_id')
                ->update(['plan_id' => $planId]);
        }
    }
}
