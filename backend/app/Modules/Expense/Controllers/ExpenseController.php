<?php

namespace App\Modules\Expense\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Requests\StoreExpenseRequest;
use App\Modules\Expense\Requests\UpdateExpenseRequest;
use App\Modules\Expense\Resources\ExpenseResource;
use App\Modules\Expense\Services\ExpenseService;
use App\Modules\Mission\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController
{
    use ApiResponseTrait;

    public function __construct(private ExpenseService $service) {}

    public function listAll(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Expense::class);

        $paginator = Expense::with('mission')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->input('per_page', 10), 100));

        return response()->json([
            'success' => true,
            'data'    => ExpenseResource::collection($paginator->items()),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'message' => 'Succès',
        ]);
    }

    public function index(string $missionUuid): JsonResponse
    {
        $mission = Mission::where('uuid', $missionUuid)->firstOrFail();
        $this->authorize('view', $mission);
        $paginator = $this->service->paginateByMission($mission);
        return $this->paginatedResponse(ExpenseResource::collection($paginator)->resource);
    }

    public function store(StoreExpenseRequest $request, string $missionUuid): JsonResponse
    {
        $mission = Mission::where('uuid', $missionUuid)->firstOrFail();
        $expense = $this->service->create($mission, $request->validated());
        return $this->successResponse(new ExpenseResource($expense), 201, 'Dépense enregistrée.');
    }

    public function show(string $uuid): JsonResponse
    {
        $expense = Expense::where('uuid', $uuid)->firstOrFail();
        $this->authorize('view', $expense);
        return $this->successResponse(new ExpenseResource($expense));
    }

    public function update(UpdateExpenseRequest $request, string $uuid): JsonResponse
    {
        $expense = Expense::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $expense);
        $expense = $this->service->update($expense, $request->validated());
        return $this->successResponse(new ExpenseResource($expense), 200, 'Dépense mise à jour.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $expense = Expense::where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $expense);
        $this->service->delete($expense);
        return $this->successResponse(null, 200, 'Dépense supprimée.');
    }

    public function uploadReceipt(Request $request, string $uuid): JsonResponse
    {
        $request->validate(['receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120']);
        $expense = Expense::where('uuid', $uuid)->firstOrFail();
        $expense = $this->service->uploadReceipt($expense, $request->file('receipt'));
        return $this->successResponse(new ExpenseResource($expense), 200, 'Justificatif uploadé.');
    }

    protected function authorize(string $ability, mixed $target): void
    {
        app(\Illuminate\Contracts\Auth\Access\Gate::class)->authorize($ability, $target);
    }
}
