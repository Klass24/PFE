<?php

namespace App\Modules\Auth\Services;

use App\Modules\User\Models\User;
use App\Modules\Company\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthService
{
    public function login(array $data): ?array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return null;
        }

        if (!$user->is_active) {
            return null;
        }

        $token = $user->createToken('api-token')->plainTextToken;

        Log::info('User logged in', ['user_id' => $user->id, 'email' => $user->email]);

        return [
            'token' => $token,
            'user'  => [
                'uuid'       => $user->uuid,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->roles->first()?->name,
                'company_id' => $user->company_id,
            ],
        ];
    }

    public function register(array $data): array
    {
        $selectedPlan = $data['plan'] ?? 'starter';

        // Résoudre plan_id depuis la table plans
        $planRecord = \App\Modules\Pricing\Models\Plan::where('slug', $selectedPlan)->first();

        $company = Company::create([
            'uuid'      => Str::uuid(),
            'name'      => $data['company_name'],
            'slug'      => Str::slug($data['company_name']) . '-' . Str::random(5),
            'email'     => $data['company_email'],
            'plan'      => $selectedPlan,
            'plan_id'   => $planRecord?->id,
            'is_active' => true,
        ]);

        $user = User::create([
            'uuid'       => Str::uuid(),
            'company_id' => $company->id,
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'is_active'  => true,
        ]);

        $user->assignRole('admin');

        $token = $user->createToken('api-token')->plainTextToken;

        Log::info('New company registered', ['company_id' => $company->id, 'user_id' => $user->id]);

        return [
            'token' => $token,
            'user'  => [
                'uuid'       => $user->uuid,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => 'admin',
                'company_id' => $company->id,
            ],
        ];
    }
}
