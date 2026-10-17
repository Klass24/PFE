<?php

namespace App\Modules\Subscription\Services;

use App\Modules\Company\Models\Company;
use App\Modules\Company\Models\Subscription;
use App\Modules\Driver\Models\Driver;
use App\Modules\User\Models\User;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function getCurrentSubscription(): ?Subscription
    {
        $company = auth()->user()->company;
        return $company->activeSubscription;
    }

    /**
     * Changement de plan par l'admin de l'entreprise (self-service).
     */
    public function changePlan(string $newPlan): Subscription
    {
        $company = auth()->user()->company;
        return $this->changePlanForCompany($company, $newPlan);
    }

    /**
     * Changement de plan par le super_admin pour une entreprise donnée.
     */
    public function changePlanForCompany(Company $company, string $newPlan, ?string $endDate = null): Subscription
    {
        $newQuotas = Company::PLAN_QUOTAS[$newPlan] ?? Company::PLAN_QUOTAS['starter'];

        // Vérifier que le nouveau plan peut accueillir les ressources existantes
        $vehicleCount = Vehicle::where('company_id', $company->id)->count();
        $driverCount  = Driver::where('company_id', $company->id)->count();
        $userCount    = User::where('company_id', $company->id)->count();

        if ($vehicleCount > $newQuotas['vehicles']) {
            throw ValidationException::withMessages([
                'plan' => "Impossible de passer au plan {$newPlan} : l'entreprise a {$vehicleCount} véhicule(s), le plan en permet {$newQuotas['vehicles']}.",
            ]);
        }
        if ($driverCount > $newQuotas['drivers']) {
            throw ValidationException::withMessages([
                'plan' => "Impossible de passer au plan {$newPlan} : l'entreprise a {$driverCount} chauffeur(s), le plan en permet {$newQuotas['drivers']}.",
            ]);
        }
        if ($userCount > $newQuotas['users']) {
            throw ValidationException::withMessages([
                'plan' => "Impossible de passer au plan {$newPlan} : l'entreprise a {$userCount} utilisateur(s), le plan en permet {$newQuotas['users']}.",
            ]);
        }

        // Mettre à jour le plan de l'entreprise (recalcule automatiquement les quotas via Company::updating)
        $company->update(['plan' => $newPlan]);

        // Annuler l'ancien abonnement actif
        Subscription::where('company_id', $company->id)
            ->where('billing_status', 'active')
            ->update(['billing_status' => 'cancelled', 'end_date' => now()]);

        // Créer le nouvel abonnement
        $subscription = Subscription::create([
            'company_id'     => $company->id,
            'plan'           => $newPlan,
            'start_date'     => now(),
            'end_date'       => $endDate ? \Carbon\Carbon::parse($endDate) : now()->addYear(),
            'billing_status' => 'active',
        ]);

        Log::info('Plan changed', [
            'company_id' => $company->id,
            'new_plan'   => $newPlan,
            'by'         => auth()->id(),
        ]);

        return $subscription;
    }

    /**
     * Retourne l'usage des quotas pour l'entreprise de l'utilisateur connecté.
     */
    public function getQuotaUsage(): array
    {
        return $this->getQuotaUsageForCompany(auth()->user()->company);
    }

    /**
     * Retourne l'usage des quotas pour une entreprise donnée.
     */
    public function getQuotaUsageForCompany(Company $company): array
    {
        return [
            'plan'     => $company->plan,
            'vehicles' => [
                'used' => Vehicle::where('company_id', $company->id)->count(),
                'max'  => $company->max_vehicles,
            ],
            'drivers' => [
                'used' => Driver::where('company_id', $company->id)->count(),
                'max'  => $company->max_drivers,
            ],
            'users' => [
                'used' => User::where('company_id', $company->id)->count(),
                'max'  => $company->max_users,
            ],
        ];
    }
}
