<?php

namespace App\Modules\Expense\Repositories;

use App\Modules\Expense\Models\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ExpenseRepositoryInterface
{
    public function paginateByMission(int $missionId, int $perPage = 15): LengthAwarePaginator;
    public function findByUuid(string $uuid): Expense;
    public function create(array $data): Expense;
    public function update(Expense $expense, array $data): Expense;
    public function delete(Expense $expense): void;
}
