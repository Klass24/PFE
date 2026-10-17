<?php

namespace App\Modules\Expense\Repositories;

use App\Modules\Expense\Models\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExpenseRepository implements ExpenseRepositoryInterface
{
    public function paginateByMission(int $missionId, int $perPage = 15): LengthAwarePaginator
    {
        return Expense::where('mission_id', $missionId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): Expense
    {
        return Expense::where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): Expense
    {
        return Expense::create($data);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update($data);
        return $expense->fresh();
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }
}
