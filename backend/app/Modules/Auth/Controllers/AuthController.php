<?php

namespace App\Modules\Auth\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController
{
    use ApiResponseTrait;

    public function __construct(private AuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (!$result) {
            return $this->errorResponse('Email ou mot de passe incorrect.', 401);
        }

        return $this->successResponse($result, 200, 'Connexion réussie.');
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());
        return $this->successResponse($result, 201, 'Compte créé avec succès.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, 200, 'Déconnexion réussie.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');
        return $this->successResponse([
            'uuid'       => $user->uuid,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->roles->first()?->name,
            'company_id' => $user->company_id,
            'is_active'  => $user->is_active,
        ]);
    }
}
