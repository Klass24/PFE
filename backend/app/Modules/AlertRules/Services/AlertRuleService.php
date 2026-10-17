<?php

namespace App\Modules\AlertRules\Services;

use App\Core\Services\BaseService;
use App\Modules\AlertRules\Models\AlertRule;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlertRuleService extends BaseService
{
    public function __construct(private NotificationService $notificationService) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return AlertRule::orderBy('created_at', 'desc')
            ->when($filters['trigger_type'] ?? null, fn($q, $t) => $q->where('trigger_type', $t))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', $filters['is_active']))
            ->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function create(array $data): AlertRule
    {
        $rule = AlertRule::create($data);
        Log::info('Alert rule created', ['rule_id' => $rule->id, 'name' => $rule->name]);
        return $rule;
    }

    public function update(AlertRule $rule, array $data): AlertRule
    {
        $rule->update($data);
        return $rule->refresh();
    }

    public function delete(AlertRule $rule): void
    {
        $rule->delete();
        Log::info('Alert rule deleted', ['rule_id' => $rule->id]);
    }

    /**
     * Evaluate all active rules for a given trigger type against trigger data.
     */
    public function evaluateRules(string $triggerType, array $triggerData, int $companyId): void
    {
        $rules = AlertRule::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('trigger_type', $triggerType)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if ($rule->evaluate($triggerData)) {
                $this->executeActions($rule, $triggerData);

                // Log execution
                DB::table('alert_rule_executions')->insert([
                    'alert_rule_id'     => $rule->id,
                    'triggered_by_type' => $triggerType,
                    'triggered_by_id'   => $triggerData['id'] ?? 0,
                    'execution_data'    => json_encode($triggerData),
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                Log::info('Alert rule triggered', ['rule' => $rule->name, 'trigger' => $triggerType]);
            }
        }
    }

    private function executeActions(AlertRule $rule, array $triggerData): void
    {
        $actions = $rule->actions ?? [];

        if (isset($actions['notification'])) {
            $channels = $actions['notification']['channels'] ?? ['dashboard'];
            $this->notificationService->createFromRule($rule, $triggerData, $channels);
        }
    }
}
