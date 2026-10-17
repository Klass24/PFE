<?php

namespace App\Modules\Admin\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Admin\Requests\AdminStoreCompanyRequest;
use App\Modules\Admin\Requests\AdminUpdateCompanyRequest;
use App\Modules\Admin\Resources\AdminCompanyResource;
use App\Modules\Company\Models\Company;
use App\Modules\Company\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminCompanyController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $paginator = Company::query()
            ->when($request->search, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($request->plan, fn($q, $v) => $q->where('plan', $v))
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->withCount(['users', 'vehicles'])
            ->with('activeSubscription')
            ->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->input('per_page', 10), 100));

        return $this->paginatedResponse(AdminCompanyResource::collection($paginator)->resource, 'Entreprises récupérées.');
    }

    public function store(AdminStoreCompanyRequest $request): JsonResponse
    {
        $data = $request->validated();

        $company = DB::transaction(function () use ($data) {
            $company = Company::create($data);

            // Créer l'abonnement initial
            Subscription::create([
                'company_id'     => $company->id,
                'plan'           => $company->plan,
                'start_date'     => now(),
                'end_date'       => now()->addYear(),
                'billing_status' => 'active',
            ]);

            return $company;
        });

        Log::info('Company created by super_admin', ['company_id' => $company->id, 'by' => auth()->id()]);

        $company->loadCount(['users', 'vehicles'])->load('activeSubscription');

        return $this->successResponse(new AdminCompanyResource($company), 201, 'Entreprise créée avec succès.');
    }

    public function show(string $uuid): JsonResponse
    {
        $company = Company::where('uuid', $uuid)
            ->withCount(['users', 'vehicles'])
            ->with('activeSubscription')
            ->firstOrFail();

        return $this->successResponse(new AdminCompanyResource($company));
    }

    public function update(AdminUpdateCompanyRequest $request, string $uuid): JsonResponse
    {
        $company = Company::where('uuid', $uuid)->firstOrFail();
        $data = $request->validated();

        // Si le plan change, vérifier les quotas avant
        if (isset($data['plan']) && $data['plan'] !== $company->plan) {
            $newQuotas = Company::PLAN_QUOTAS[$data['plan']] ?? Company::PLAN_QUOTAS['starter'];
            $vehicleCount = $company->vehicles()->count();
            $driverCount  = \App\Modules\Driver\Models\Driver::where('company_id', $company->id)->count();
            $userCount    = $company->users()->count();

            $errors = [];
            if ($vehicleCount > $newQuotas['vehicles']) {
                $errors[] = "{$vehicleCount} véhicule(s) existant(s), le plan en permet {$newQuotas['vehicles']}.";
            }
            if ($driverCount > $newQuotas['drivers']) {
                $errors[] = "{$driverCount} chauffeur(s) existant(s), le plan en permet {$newQuotas['drivers']}.";
            }
            if ($userCount > $newQuotas['users']) {
                $errors[] = "{$userCount} utilisateur(s) existant(s), le plan en permet {$newQuotas['users']}.";
            }
            if (!empty($errors)) {
                return $this->errorResponse('Impossible de changer le plan : ' . implode(' ', $errors), 422);
            }
        }

        $company->update($data);

        Log::info('Company updated by super_admin', ['company_id' => $company->id, 'changes' => $data, 'by' => auth()->id()]);

        $company->fresh()->loadCount(['users', 'vehicles'])->load('activeSubscription');

        return $this->successResponse(new AdminCompanyResource($company), 200, 'Entreprise mise à jour.');
    }

    public function suspend(string $uuid): JsonResponse
    {
        $company = Company::where('uuid', $uuid)->firstOrFail();
        $company->suspend();

        return $this->successResponse(new AdminCompanyResource($company->fresh()), 200, 'Entreprise suspendue.');
    }

    public function activate(string $uuid): JsonResponse
    {
        $company = Company::where('uuid', $uuid)->firstOrFail();
        $company->activate();

        return $this->successResponse(new AdminCompanyResource($company->fresh()), 200, 'Entreprise activée.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $company = Company::where('uuid', $uuid)->firstOrFail();
        $company->delete();

        return $this->successResponse(null, 200, 'Entreprise supprimée.');
    }
}
