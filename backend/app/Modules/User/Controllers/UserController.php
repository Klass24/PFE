<?php

namespace App\Modules\User\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\StoreUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController
{
    use ApiResponseTrait;

    public function __construct(private UserService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['role', 'name', 'email', 'search', 'status', 'per_page']);

        // Super admin : ne voit que les admin/manager des entreprises
        if (auth()->user()->hasRole('super_admin') && empty($filters['role'])) {
            $filters['role'] = ['admin', 'manager'];
        }

        $paginator = $this->service->paginate($filters);
        return $this->paginatedResponse(UserResource::collection($paginator)->resource, 'Utilisateurs récupérés.');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $user = $this->service->create($request->validated());
        return $this->successResponse(new UserResource($user), 201, 'Utilisateur créé avec succès.');
    }

    public function show(string $uuid): JsonResponse
    {
        $query = User::where('uuid', $uuid);

        // Super admin : accès cross-tenant
        if (!auth()->user()->hasRole('super_admin')) {
            $query->where('company_id', auth()->user()->company_id);
        }

        $user = $query->firstOrFail();
        $this->authorize('view', $user);
        return $this->successResponse(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, string $uuid): JsonResponse
    {
        $user = User::where('uuid', $uuid)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();
        $this->authorize('update', $user);
        $user = $this->service->update($user, $request->validated());
        return $this->successResponse(new UserResource($user), 200, 'Utilisateur mis à jour.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $user = User::where('uuid', $uuid)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();
        $this->authorize('delete', $user);
        $this->service->delete($user);
        return $this->successResponse(null, 200, 'Utilisateur supprimé.');
    }

    protected function authorize(string $ability, mixed $target): void
    {
        app(\Illuminate\Contracts\Auth\Access\Gate::class)->authorize($ability, $target);
    }
}
