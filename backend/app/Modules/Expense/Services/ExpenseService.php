<?php

namespace App\Modules\Expense\Services;

use App\Core\Services\BaseService;
use App\Modules\Expense\Events\ExpenseCreatedEvent;
use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Repositories\ExpenseRepositoryInterface;
use App\Modules\Mission\Models\Mission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ExpenseService extends BaseService
{
    public function __construct(private ExpenseRepositoryInterface $repo) {}

    public function paginateByMission(Mission $mission): LengthAwarePaginator
    {
        return $this->repo->paginateByMission($mission->id);
    }

    public function create(Mission $mission, array $data): Expense
    {
        // M4 — Une dépense ne peut être créée que sur une mission active
        if (!in_array($mission->status, ['planned', 'in_progress'])) {
            throw ValidationException::withMessages([
                'mission_id' => 'Les dépenses ne peuvent être ajoutées qu\'à une mission active.',
            ]);
        }

        $data['mission_id']  = $mission->id;
        $data['company_id']  = auth()->user()->company_id;
        $data['created_by']  = auth()->id();

        // M6 — Déduplication offline : si device_id + date_saisie_reelle déjà enregistrés, ignorer
        if (!empty($data['device_id']) && !empty($data['date_saisie_reelle'])) {
            $existing = Expense::withoutGlobalScopes()
                ->where('device_id', $data['device_id'])
                ->where('date_saisie_reelle', $data['date_saisie_reelle'])
                ->where('mission_id', $mission->id)
                ->first();

            if ($existing) {
                Log::info('Duplicate expense ignored (device_id dedup)', ['expense_id' => $existing->id]);
                return $existing;
            }
        }

        $data['date_reception_serveur'] = now();
        $data['sync_status']            = 'synced';

        $expense = $this->repo->create($data);

        event(new ExpenseCreatedEvent($expense));
        Log::info('Expense created', ['expense_id' => $expense->id, 'mission_id' => $mission->id]);

        return $expense;
    }

    public function update(Expense $expense, array $data): Expense
    {
        return $this->repo->update($expense, $data);
    }

    public function delete(Expense $expense): void
    {
        if ($expense->receipt_image) {
            Storage::disk('public')->delete($expense->receipt_image);
        }
        $this->repo->delete($expense);
    }

    public function uploadReceipt(Expense $expense, \Illuminate\Http\UploadedFile $file): Expense
    {
        if ($expense->receipt_image) {
            Storage::disk('public')->delete($expense->receipt_image);
        }
        $path = $file->store('expenses/receipts', 'public');
        return $this->repo->update($expense, ['receipt_image' => $path]);
    }
}
