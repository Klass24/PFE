<?php

namespace Tests\Feature;

use App\Modules\AlertRules\Models\AlertRule;
use App\Modules\AlertRules\Services\AlertRuleService;
use App\Modules\Notification\Services\NotificationService;
use Mockery;
use Tests\TestCase;

class AlertRuleTest extends TestCase
{
    public function test_alert_rule_can_be_created_listed_updated_and_deleted(): void
    {
        $this->actingAsAdmin();
        $response = $this->postJson('/api/v1/alert-rules', [
            'name' => 'High expense',
            'trigger_type' => 'expense_created',
            'conditions' => ['amount' => ['operator' => '>', 'value' => 100]],
            'actions' => ['notification' => ['channels' => ['dashboard']]],
            'priority' => 'high',
        ]);
        $response->assertCreated()->assertJsonPath('data.name', 'High expense');
        $uuid = $response->json('data.uuid');

        $this->getJson('/api/v1/alert-rules?trigger_type=expense_created')
            ->assertOk()->assertJsonFragment(['uuid' => $uuid]);
        $this->putJson('/api/v1/alert-rules/' . $uuid, ['name' => 'Updated', 'is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->deleteJson('/api/v1/alert-rules/' . $uuid)->assertOk();
        $this->assertDatabaseMissing('alert_rules', ['uuid' => $uuid]);
    }

    public function test_only_matching_active_rules_trigger_notifications_and_execution_logs(): void
    {
        $this->actingAsAdmin();
        $rule = AlertRule::create([
            'name' => 'High expense',
            'trigger_type' => 'expense_created',
            'conditions' => ['amount' => ['operator' => '>', 'value' => 100]],
            'actions' => ['notification' => ['channels' => ['dashboard']]],
            'is_active' => true,
        ]);
        AlertRule::create([
            'name' => 'Inactive',
            'trigger_type' => 'expense_created',
            'conditions' => [],
            'actions' => ['notification' => ['channels' => ['dashboard']]],
            'is_active' => false,
        ]);
        $notification = Mockery::mock(NotificationService::class);
        $notification->shouldReceive('createFromRule')->once()
            ->with(Mockery::on(fn ($actual) => $actual->id === $rule->id), ['id' => 42, 'amount' => 200], ['dashboard']);
        $service = new AlertRuleService($notification);

        $service->evaluateRules('expense_created', ['id' => 41, 'amount' => 50], $this->company->id);
        $service->evaluateRules('expense_created', ['id' => 42, 'amount' => 200], $this->company->id);
        $this->assertDatabaseCount('alert_rule_executions', 1);
        $this->assertDatabaseHas('alert_rule_executions', [
            'alert_rule_id' => $rule->id,
            'triggered_by_id' => 42,
            'triggered_by_type' => 'expense_created',
        ]);
    }
}
