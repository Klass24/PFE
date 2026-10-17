<?php

namespace App\Modules\Pricing\Repositories;

use App\Modules\Pricing\Models\Plan;
use Illuminate\Database\Eloquent\Collection;

interface PlanRepositoryInterface
{
    public function all(): Collection;
    public function allActive(): Collection;
    public function findByUuid(string $uuid): ?Plan;
    public function findBySlug(string $slug): ?Plan;
    public function create(array $data): Plan;
    public function update(Plan $plan, array $data): Plan;
    public function delete(Plan $plan): void;
}
