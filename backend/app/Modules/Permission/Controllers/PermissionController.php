<?php

namespace App\Modules\Permission\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController
{
    use ApiResponseTrait;

    /**
     * GET /permissions — liste toutes les permissions disponibles.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['super_admin', 'admin'])) {
            return $this->errorResponse('Action non autorisée.', 403);
        }

        $permissions = Permission::where('guard_name', 'sanctum')
            ->orderBy('name')
            ->pluck('name');

        // Grouper par module
        $grouped = $permissions->groupBy(fn (string $p) => explode('.', $p)[0]);

        return $this->successResponse($grouped);
    }

    /**
     * GET /roles/{roleName}/permissions — permissions d'un rôle.
     */
    public function rolePermissions(string $roleName): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasRole('super_admin')) {
            return $this->errorResponse('Action non autorisée.', 403);
        }

        if (!in_array($roleName, ['admin', 'manager', 'driver'])) {
            return $this->errorResponse('Rôle invalide.', 422);
        }

        $role = Role::where('name', $roleName)->where('guard_name', 'sanctum')->firstOrFail();
        $rolePermissions = $role->permissions->pluck('name');

        return $this->successResponse([
            'role'        => $roleName,
            'permissions' => $rolePermissions,
        ]);
    }

    /**
     * PUT /roles/{roleName}/permissions — mettre à jour les permissions d'un rôle.
     * Super admin uniquement, pour admin/manager.
     */
    public function updateRolePermissions(Request $request, string $roleName): JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasRole('super_admin')) {
            return $this->errorResponse('Action non autorisée.', 403);
        }

        if (!in_array($roleName, ['admin', 'manager'])) {
            return $this->errorResponse('Seuls les rôles admin et manager peuvent être modifiés.', 422);
        }

        $validated = $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::where('name', $roleName)->where('guard_name', 'sanctum')->firstOrFail();
        $role->syncPermissions($validated['permissions']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return $this->successResponse([
            'role'        => $roleName,
            'permissions' => $role->permissions->pluck('name'),
        ], 200, 'Permissions du rôle mises à jour.');
    }

    /**
     * GET /users/{uuid}/permissions — permissions d'un utilisateur.
     * Admin : seulement les drivers de son entreprise.
     * Super admin : tout utilisateur.
     */
    public function userPermissions(string $uuid): JsonResponse
    {
        $authUser = auth()->user();

        $target = $this->resolveUser($authUser, $uuid);
        if ($target instanceof JsonResponse) {
            return $target;
        }

        return $this->successResponse([
            'user_uuid'   => $target->uuid,
            'name'        => $target->name,
            'role'        => $target->getRoleNames()->first(),
            'permissions' => $target->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * PUT /users/{uuid}/permissions — modifier les permissions directes d'un utilisateur.
     * Admin : seulement les drivers de son entreprise.
     * Super admin : tout utilisateur.
     */
    public function updateUserPermissions(Request $request, string $uuid): JsonResponse
    {
        $authUser = auth()->user();

        $target = $this->resolveUser($authUser, $uuid);
        if ($target instanceof JsonResponse) {
            return $target;
        }

        // Admin ne peut modifier que les drivers
        if ($authUser->hasRole('admin') && !$target->hasRole('driver')) {
            return $this->errorResponse('Vous ne pouvez modifier que les permissions des chauffeurs.', 403);
        }

        $validated = $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $target->syncPermissions($validated['permissions']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return $this->successResponse([
            'user_uuid'   => $target->uuid,
            'permissions' => $target->getAllPermissions()->pluck('name'),
        ], 200, 'Permissions utilisateur mises à jour.');
    }

    /**
     * Résout l'utilisateur cible en respectant le tenant.
     */
    private function resolveUser(User $authUser, string $uuid): User|JsonResponse
    {
        if (!$authUser->hasAnyRole(['super_admin', 'admin'])) {
            return $this->errorResponse('Action non autorisée.', 403);
        }

        $query = User::where('uuid', $uuid);

        // Admin ne voit que les utilisateurs de son entreprise
        if ($authUser->hasRole('admin')) {
            $query->where('company_id', $authUser->company_id);
        }

        $target = $query->first();

        if (!$target) {
            return $this->errorResponse('Utilisateur introuvable.', 404);
        }

        return $target;
    }
}
