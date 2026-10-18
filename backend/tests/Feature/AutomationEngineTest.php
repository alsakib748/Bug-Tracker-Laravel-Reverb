<?php

namespace Tests\Feature;

use App\Events\IssueCreated;
use App\Jobs\Automation\EvaluateAutomationRule;
use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Services\Automation\AutomationExecutionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutomationEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->project = Project::factory()->create(['created_by' => $this->admin->id]);

        $this->project->members()->syncWithoutDetaching([
            $this->admin->id => ['role' => 'manager', 'joined_at' => now()],
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRule(array $overrides = []): AutomationRule
    {
        return AutomationRule::create(array_merge([
            'project_id' => $this->project->id,
            'name' => 'Rule '.uniqid(),
            'trigger_type' => 'issue.created',
            'conditions' => ['match' => 'all', 'rules' => []],
            'actions' => [
                ['type' => 'change_priority', 'config' => ['priority' => 'high']],
            ],
            'is_enabled' => true,
            'run_on_actor_changes' => false,
            'created_by' => $this->admin->id,
            'version' => 1,
        ], $overrides));
    }

    private function makeIssue(string $priority = 'low'): Issue
    {
        return Issue::factory()->create([
            'project_id' => $this->project->id,
            'priority' => $priority,
        ]);
    }

    public function test_issue_created_event_queues_rule_evaluation(): void
    {
        Queue::fake();

        $this->makeRule();

        $issue = $this->makeIssue();

        event(new IssueCreated($issue, 'evt-1', $this->admin->id));

        Queue::assertPushed(EvaluateAutomationRule::class);
    }

    public function test_disabled_rule_is_not_evaluated(): void
    {
        Queue::fake();

        $this->makeRule(['is_enabled' => false]);

        $issue = $this->makeIssue();

        event(new IssueCreated($issue, 'evt-1', $this->admin->id));

        Queue::assertNotPushed(EvaluateAutomationRule::class);
    }

    public function test_duplicate_execution_for_the_same_event_is_rejected(): void
    {
        $rule = $this->makeRule();

        $payload = [
            'automation_rule_id' => $rule->id,
            'rule_version' => 1,
            'project_id' => $this->project->id,
            'event_id' => 'evt-dup',
            'event_type' => 'issue.created',
            'status' => 'succeeded',
        ];

        AutomationExecution::create($payload);

        $this->expectException(QueryException::class);

        AutomationExecution::create($payload);
    }

    public function test_matching_rule_executes_its_actions(): void
    {
        $rule = $this->makeRule([
            'conditions' => [
                'match' => 'all',
                'rules' => [['field' => 'priority', 'operator' => 'equals', 'value' => 'low']],
            ],
            'actions' => [
                ['type' => 'change_priority', 'config' => ['priority' => 'high']],
            ],
        ]);

        $issue = $this->makeIssue('low');

        event(new IssueCreated($issue, 'evt-1', $this->admin->id));

        $this->assertSame('high', $issue->fresh()->priority->value);

        $this->assertDatabaseHas('automation_executions', [
            'automation_rule_id' => $rule->id,
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseHas('automation_action_executions', [
            'action_type' => 'change_priority',
            'status' => 'succeeded',
        ]);
    }

    public function test_rule_with_unmet_conditions_is_skipped(): void
    {
        $rule = $this->makeRule([
            'conditions' => [
                'match' => 'all',
                'rules' => [['field' => 'priority', 'operator' => 'equals', 'value' => 'critical']],
            ],
        ]);

        $issue = $this->makeIssue('low');

        event(new IssueCreated($issue, 'evt-1', $this->admin->id));

        $this->assertSame('low', $issue->fresh()->priority->value);

        $this->assertDatabaseHas('automation_executions', [
            'automation_rule_id' => $rule->id,
            'status' => 'skipped',
        ]);
    }

    public function test_loop_guard_skips_runaway_chains(): void
    {
        $rule = $this->makeRule();

        $issue = $this->makeIssue('low');

        Cache::put("automation_loop:{$issue->id}", 3, now()->addMinutes(5));

        $execution = app(AutomationExecutionService::class)->execute($rule, [
            'event_id' => 'evt-1',
            'event_type' => 'issue.created',
            'subject_type' => Issue::class,
            'subject_id' => $issue->id,
            'issue' => $issue->toArray(),
        ]);

        $this->assertSame('skipped', $execution->fresh()->status);
        $this->assertSame('loop_guard_exceeded', $execution->fresh()->error_code);
        $this->assertSame('low', $issue->fresh()->priority->value);
    }

    public function test_automation_does_not_react_to_its_own_changes(): void
    {
        // A rule that reacts to issue.updated would otherwise loop when the
        // change_priority action fires an IssueUpdated event.
        $this->makeRule([
            'trigger_type' => 'issue.updated',
            'actions' => [['type' => 'change_priority', 'config' => ['priority' => 'critical']]],
        ]);

        $creator = $this->makeRule([
            'trigger_type' => 'issue.created',
            'actions' => [['type' => 'change_priority', 'config' => ['priority' => 'high']]],
        ]);

        $issue = $this->makeIssue('low');

        event(new IssueCreated($issue, 'evt-1', $this->admin->id));

        // The chained issue.updated rule must not run while inside automation.
        $this->assertDatabaseMissing('automation_executions', [
            'event_type' => 'issue.updated',
        ]);

        $this->assertDatabaseHas('automation_executions', [
            'automation_rule_id' => $creator->id,
            'status' => 'succeeded',
        ]);
    }

    public function test_metadata_endpoint_exposes_the_registry(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/automation-options')
            ->assertOk()
            ->assertJsonStructure(['triggers', 'operators', 'actions', 'fields']);
    }

    public function test_project_rules_are_listed(): void
    {
        $this->makeRule();

        Sanctum::actingAs($this->admin);

        $this->getJson("/api/projects/{$this->project->id}/automation-rules")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_store_rule_persists_a_normalized_definition(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson("/api/projects/{$this->project->id}/automation-rules", [
            'name' => 'Notify on high priority',
            'trigger_type' => 'issue.created',
            'conditions' => [
                'match' => 'all',
                'rules' => [['field' => 'priority', 'operator' => 'equals', 'value' => 'high']],
            ],
            'actions' => [
                ['type' => 'notify_users', 'config' => ['recipients' => [$this->admin->id], 'message' => 'Heads up']],
            ],
            'is_enabled' => true,
        ])->assertStatus(201);

        $this->assertDatabaseHas('automation_rules', [
            'name' => 'Notify on high priority',
            'trigger_type' => 'issue.created',
            'is_enabled' => true,
        ]);

        $rule = AutomationRule::firstWhere('name', 'Notify on high priority');

        // Store normalized the action to a known type.
        $this->assertSame('notify_users', $rule->actions[0]['type']);
        $this->assertSame([$this->admin->id], $rule->actions[0]['config']['recipients']);
        $this->assertSame('high', $rule->conditions['rules'][0]['value']);
        $this->assertSame(1, $rule->version);
    }

    public function test_store_rule_rejects_fields_not_allowed_for_the_trigger(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson("/api/projects/{$this->project->id}/automation-rules", [
            'name' => 'Bad field',
            'trigger_type' => 'issue.created',
            'conditions' => [
                'match' => 'all',
                'rules' => [['field' => 'to_status', 'operator' => 'equals', 'value' => 'closed']],
            ],
            'actions' => [['type' => 'change_priority', 'config' => ['priority' => 'high']]],
        ])->assertStatus(422);
    }

    public function test_store_rule_rejects_unknown_action_types(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson("/api/projects/{$this->project->id}/automation-rules", [
            'name' => 'Bad action',
            'trigger_type' => 'issue.created',
            'conditions' => ['match' => 'all', 'rules' => []],
            'actions' => [['type' => 'delete_issue', 'config' => []]],
        ])->assertStatus(422);
    }

    public function test_update_rule_normalizes_and_bumps_the_version(): void
    {
        $rule = $this->makeRule();

        Sanctum::actingAs($this->admin);

        $this->putJson("/api/automation-rules/{$rule->id}", [
            'name' => 'Renamed rule',
            'trigger_type' => 'issue.created',
            'conditions' => [
                'match' => 'all',
                'rules' => [['field' => 'priority', 'operator' => 'equals', 'value' => 'critical']],
            ],
            'actions' => [['type' => 'change_priority', 'config' => ['priority' => 'critical']]],
        ])->assertOk();

        $rule->refresh();

        $this->assertSame('Renamed rule', $rule->name);
        $this->assertSame(2, $rule->version);
        $this->assertSame('critical', $rule->conditions['rules'][0]['value']);
        $this->assertSame('critical', $rule->actions[0]['config']['priority']);
    }

    public function test_rule_can_be_disabled_and_enabled(): void
    {
        $rule = $this->makeRule();

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/automation-rules/{$rule->id}/disable")->assertOk();
        $this->assertFalse($rule->fresh()->is_enabled);

        $this->postJson("/api/automation-rules/{$rule->id}/enable")->assertOk();
        $this->assertTrue($rule->fresh()->is_enabled);
    }

    public function test_executions_are_listed_for_a_project(): void
    {
        $this->makeRule();

        $issue = $this->makeIssue('low');

        event(new IssueCreated($issue, 'evt-1', $this->admin->id));

        Sanctum::actingAs($this->admin);

        $this->getJson("/api/projects/{$this->project->id}/automation-executions")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
