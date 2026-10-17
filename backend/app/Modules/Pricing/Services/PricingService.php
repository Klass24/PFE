<?php

namespace App\Modules\Pricing\Services;

use App\Modules\Company\Models\Company;
use App\Modules\Driver\Models\Driver;
use App\Modules\Pricing\Models\Plan;
use App\Modules\Pricing\Repositories\PlanRepositoryInterface;
use App\Modules\User\Models\User;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function __construct(private PlanRepositoryInterface $repo) {}

    /**
     * Liste publique des plans actifs (landing page, registration).
     */
    public function getPublicPlans(): Collection
    {
        return $this->repo->allActive();
    }

    /**
     * Liste complète des plans (admin).
     */
    public function getAllPlans(): Collection
    {
        return $this->repo->all();
    }

    /**
     * Détail d'un plan par UUID.
     */
    public function getByUuid(string $uuid): Plan
    {
        $plan = $this->repo->findByUuid($uuid);
        if (!$plan) {
            abort(404, 'Plan introuvable.');
        }
        return $plan;
    }

    /**
     * Détail d'un plan par slug.
     */
    public function getBySlug(string $slug): Plan
    {
        $plan = $this->repo->findBySlug($slug);
        if (!$plan) {
            abort(404, 'Plan introuvable.');
        }
        return $plan;
    }

    /**
     * Créer un plan (super_admin).
     */
    public function create(array $data): Plan
    {
        $plan = $this->repo->create($data);
        Log::info('Plan created', ['plan_id' => $plan->id, 'slug' => $plan->slug]);
        return $plan;
    }

    /**
     * Mettre à jour un plan (super_admin).
     */
    public function update(Plan $plan, array $data): Plan
    {
        $plan = $this->repo->update($plan, $data);
        Log::info('Plan updated', ['plan_id' => $plan->id, 'slug' => $plan->slug]);
        return $plan;
    }

    /**
     * Supprimer un plan (super_admin). Bloque si des entreprises l'utilisent.
     */
    public function delete(Plan $plan): void
    {
        if ($plan->companies()->exists()) {
            throw ValidationException::withMessages([
                'plan' => 'Impossible de supprimer ce plan : des entreprises l\'utilisent.',
            ]);
        }
        $this->repo->delete($plan);
        Log::info('Plan deleted', ['plan_id' => $plan->id, 'slug' => $plan->slug]);
    }

    /**
     * Comparaison du plan actuel vs un plan cible pour une entreprise.
     */
    public function comparePlans(Company $company, string $targetPlanSlug): array
    {
        $currentPlan = $this->repo->findBySlug($company->plan);
        $targetPlan  = $this->repo->findBySlug($targetPlanSlug);

        if (!$targetPlan) {
            abort(404, 'Plan cible introuvable.');
        }

        $vehicleCount = Vehicle::where('company_id', $company->id)->count();
        $driverCount  = Driver::where('company_id', $company->id)->count();
        $userCount    = User::where('company_id', $company->id)->count();

        $canUpgrade = $vehicleCount <= $targetPlan->max_vehicles
            && $driverCount <= $targetPlan->max_drivers
            && $userCount <= $targetPlan->max_users;

        $isUpgrade = $targetPlan->sort_order > ($currentPlan?->sort_order ?? 0);

        return [
            'current_plan' => $currentPlan,
            'target_plan'  => $targetPlan,
            'usage' => [
                'vehicles' => ['used' => $vehicleCount, 'current_max' => $company->max_vehicles, 'target_max' => $targetPlan->max_vehicles],
                'drivers'  => ['used' => $driverCount,  'current_max' => $company->max_drivers,  'target_max' => $targetPlan->max_drivers],
                'users'    => ['used' => $userCount,    'current_max' => $company->max_users,    'target_max' => $targetPlan->max_users],
            ],
            'can_switch'   => $canUpgrade,
            'is_upgrade'   => $isUpgrade,
            'price_diff'   => [
                'monthly' => ($targetPlan->price_monthly ?? 0) - ($currentPlan->price_monthly ?? 0),
                'yearly'  => ($targetPlan->price_yearly ?? 0) - ($currentPlan->price_yearly ?? 0),
            ],
        ];
    }

    /**
     * Récupérer le plan actuel de l'entreprise connectée avec détails complets.
     */
    public function getCurrentPlanDetails(): array
    {
        $company = auth()->user()->company;
        $plan    = $this->repo->findBySlug($company->plan);

        $vehicleCount = Vehicle::where('company_id', $company->id)->count();
        $driverCount  = Driver::where('company_id', $company->id)->count();
        $userCount    = User::where('company_id', $company->id)->count();

        return [
            'plan'  => $plan,
            'usage' => [
                'vehicles' => ['used' => $vehicleCount, 'max' => $company->max_vehicles],
                'drivers'  => ['used' => $driverCount,  'max' => $company->max_drivers],
                'users'    => ['used' => $userCount,    'max' => $company->max_users],
            ],
            'company' => [
                'uuid' => $company->uuid,
                'name' => $company->name,
            ],
        ];
    }
}
