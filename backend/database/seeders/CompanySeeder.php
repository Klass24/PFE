<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Company\Models\Company;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::firstOrCreate(
            ['slug' => 'demo-company'],
            [
                'uuid'      => Str::uuid(),
                'name'      => 'Demo Company',
                'email'     => 'contact@demo-company.com',
                'phone'     => '+33 1 23 45 67 89',
                'plan'      => 'pro',
                'is_active' => true,
            ]
        );
    }
}
