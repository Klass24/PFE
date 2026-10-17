<?php

namespace App\Modules\Permission\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Permission\Requests\StoreRoleRequest;
use App\Modules\Permission\Requests\UpdateRoleRequest;
use App\Modules\Permission\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class RoleController
{
    use ApiResponseTrait;

    /** Rôles système non modifiables / supprimables */
    private const PROTECTED_ROLES = ['super_admin', 'admin', 'manager', 'driver'];

    /**
     * GET /roles — liste tous les rôles.
     */
    public function index(): JsonResponse
    {
        $this->ensureSuperAdmin();

        $roles = Role::where('guard_name', 'sanctum')
            ->withCount('users')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return $this->successResponse(RoleResource::collection($roles), 200, 'Rôles récupérés.');
    }

    /**
     * POST /roles — créer un nouveau rôle.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => 'sanctum',
        ]);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        $role->load('permissions');

        Log::info('Role created', ['role' => $role->name, 'by' => auth()->id()]);

        return $this->successResponse(new RoleResource($role), 201, 'Rôle créé avec succès.');
    }

    /**
     * GET /roles/{id} — détail d'un rôle.
     */
    public function show(int $id): JsonResponse
    {
        $this->ensureSuperAdmin();

        $role = Role::where('guard_name', 'sanctum')
            ->withCount('users')
            ->with('permissions')
            ->findOrFail($id);

        return $this->successResponse(new RoleResource($role));
    }

    /**
     * PUT /roles/{id} — mettre à jour un rôle.
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'sanctum')->findOrFail($id);

        if (in_array($role->name, self::PROTECTED_ROLES)) {
            return $this->errorResponse('Les rôles système ne peuvent pas être renommés.', 422);
        }

        $data = $request->validated();

        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (array_key_exists('permissions', $data)) {
            $role->syncPermissions($data['permissions'] ?? []);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role->load('permissions');
        $role->loadCount('users');

        Log::info('Role updated', ['role' => $role->name, 'by' => auth()->id()]);

        return $this->successResponse(new RoleResource($role), 200, 'Rôle mis à jour.');
    }

    /**
     * DELETE /roles/{id} — supprimer un rôle.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->ensureSuperAdmin();

        $role = Role::where('guard_name', 'sanctum')->findOrFail($id);

        if (in_array($role->name, self::PROTECTED_ROLES)) {
            return $this->errorResponse('Les rôles système ne peuvent pas être supprimés.', 422);
        }

        if ($role->users()->count() > 0) {
            return $this->errorResponse('Impossible de supprimer un rôle assigné à des utilisateurs.', 422);
        }

        $roleName = $role->name;
        $role->delete();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Log::info('Role deleted', ['role' => $roleName, 'by' => auth()->id()]);

        return $this->successResponse(null, 200, 'Rôle supprimé.');
    }

    private function ensureSuperAdmin(): void
    {
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403, 'Action non autorisée.');
        }
    }
}
