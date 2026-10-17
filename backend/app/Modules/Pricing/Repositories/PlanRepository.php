<?php

namespace App\Modules\Pricing\Repositories;

use App\Modules\Pricing\Models\Plan;
use Illuminate\Database\Eloquent\Collection;

class PlanRepository implements PlanRepositoryInterface
{
    public function all(): Collection
    {
        return Plan::ordered()->get();
    }

    public function allActive(): Collection
    {
        return Plan::active()->ordered()->get();
    }

    public function findByUuid(string $uuid): ?Plan
    {
        return Plan::where('uuid', $uuid)->first();
    }

    public function findBySlug(string $slug): ?Plan
    {
        return Plan::where('slug', $slug)->first();
    }

    public function create(array $data): Plan
    {
        return Plan::create($data);
    }

    public function update(Plan $plan, array $data): Plan
    {
        $plan->update($data);
        return $plan->fresh();
    }

    public function delete(Plan $plan): void
    {
        $plan->delete();
    }
}
