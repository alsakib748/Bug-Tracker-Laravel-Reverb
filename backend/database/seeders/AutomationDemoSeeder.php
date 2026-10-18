<?php

namespace Database\Seeders;

use App\Enums\AutomationTrigger;
use App\Models\AutomationActionExecution;
use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Seeds realistic, real-life automation rules for a bug tracking project.
 *
 * Rules are written against the Aurora demo project when it exists, otherwise
 * against the project with the most issues, and reference real project members,
 * labels and issues so the automation UI has believable data.
 */
class AutomationDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private Project $project;

    /** @var Collection<int, User> */
    private Collection $developers;

    /** @var Collection<int, User> */
    private Collection $testers;

    /** @var Collection<int, User> */
    private Collection $managers;

    private User $lead;

    private User $owner;

    public function run(): void
    {
        $this->project = Project::query()->where('code', 'AUR')->first()
            ?? Project::query()->withCount('issues')->orderByDesc('issues_count')->first()
            ?? Project::query()->first();

        if (! $this->project) {
            $this->command->warn('No projects found — run DatabaseSeeder (and BoardDemoSeeder) first.');

            return;
        }

        $this->resolveCast();

        // Unique index on (project_id, name) + soft deletes, so clear first.
        AutomationRule::query()->where('project_id', $this->project->id)->forceDelete();

        $created = [];

        foreach ($this->definitions() as $definition) {
            $created[] = AutomationRule::query()->create([
                'project_id' => $this->project->id,
                'name' => $definition['name'],
                'description' => $definition['description'],
                'trigger_type' => $definition['trigger'],
                'conditions' => $definition['conditions'],
                'actions' => $definition['actions'],
                'is_enabled' => $definition['is_enabled'] ?? true,
                'run_on_actor_changes' => $definition['run_on_actor_changes'] ?? false,
                'cooldown_seconds' => $definition['cooldown_seconds'] ?? null,
                'version' => $definition['version'] ?? 1,
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]);
        }

        $executions = $this->seedExecutions($created);

        $this->command->info("Automation demo data seeded for project \"{$this->project->name}\" ({$this->project->code}).");
        $this->command->info('  Rules: '.count($created).' ('.collect($created)->where('is_enabled', true)->count().' enabled)');
        $this->command->info("  Execution history records: {$executions}");
    }

    private function resolveCast(): void
    {
        $members = $this->project->members;

        $this->developers = $members->where('pivot.role', 'developer')->values();
        $this->testers = $members->where('pivot.role', 'tester')->values();
        $this->managers = $members->where('pivot.role', 'manager')->values();

        // Fallbacks so the seeder still works on sparse projects.
        $this->lead = $this->developers->first()
            ?? $members->first()
            ?? User::role('Developer')->first()
            ?? User::query()->first();

        $this->owner = $this->managers->first()
            ?? User::role('Admin')->first()
            ?? $this->lead;

        if ($this->testers->isEmpty()) {
            $this->testers = $members->values();
        }

        if ($this->managers->isEmpty()) {
            $this->managers = collect([$this->owner]);
        }
    }

    /**
     * @return list<int>
     */
    private function ids(Collection $users, ?User $extra = null): array
    {
        $ids = $users->pluck('id');

        if ($extra) {
            $ids->push($extra->id);
        }

        return $ids->filter()->unique()->values()->map(fn ($id) => (int) $id)->all();
    }

    private function labelId(string $name): ?int
    {
        $label = $this->project->labels()->where('name', $name)->first();

        return $label ? (int) $label->id : null;
    }

    /**
     * The realistic rule set for a software bug tracker.
     *
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        $developers = $this->ids($this->developers);
        $testers = $this->ids($this->testers);
        $managers = $this->ids($this->managers, $this->owner);

        $security = $this->labelId('security');
        $design = $this->labelId('design');
        $techDebt = $this->labelId('tech-debt');
        $performance = $this->labelId('performance');

        $rules = [];

        // 1. Auto-assign every new bug to the lead developer.
        $rules[] = [
            'name' => 'Auto-assign new bugs to the lead developer',
            'description' => 'Every bug that is reported lands on the lead developer so it never sits unowned.',
            'trigger' => AutomationTrigger::ISSUE_CREATED->value,
            'conditions' => $this->all([
                $this->condition('type', 'equals', 'bug'),
            ]),
            'actions' => [
                $this->action('assign_issue', ['user_id' => $this->lead->id]),
                $this->action('notify_users', [
                    'recipients' => [$this->lead->id],
                    'message' => 'A new bug was reported and auto-assigned to you.',
                ]),
            ],
        ];

        // 2. Triage alert for urgent bugs.
        $rules[] = [
            'name' => 'Triage alert for critical and high priority bugs',
            'description' => 'Ping the engineering leads whenever an urgent bug is filed so it is triaged the same day.',
            'trigger' => AutomationTrigger::ISSUE_CREATED->value,
            'conditions' => $this->all([
                $this->condition('type', 'equals', 'bug'),
                $this->condition('priority', 'in', ['critical', 'high']),
            ]),
            'actions' => [
                $this->action('add_comment', [
                    'body' => 'This looks urgent. Please confirm the impact, add reproduction steps and set a severity before picking it up.',
                ]),
                $this->action('notify_users', [
                    'recipients' => array_values(array_unique(array_merge($managers, $testers))),
                    'message' => 'An urgent bug needs triage.',
                ]),
            ],
            'cooldown_seconds' => 300,
        ];

        // 3. SLA due date for critical issues.
        $rules[] = [
            'name' => 'Set a 2-day SLA for critical issues',
            'description' => 'Critical priorities get an automatic due date so they surface in the overdue reports.',
            'trigger' => AutomationTrigger::ISSUE_CREATED->value,
            'conditions' => $this->all([
                $this->condition('priority', 'equals', 'critical'),
            ]),
            'actions' => [
                $this->action('set_due_date', ['offset_days' => 2]),
                $this->action('add_comment', [
                    'body' => 'Critical issues carry a 2-day SLA. Please update the status or comment if the estimate changes.',
                ]),
            ],
        ];

        // 4. Security review workflow.
        if ($security) {
            $rules[] = [
                'name' => 'Request a security review for security-labelled issues',
                'description' => 'Adding the security label pulls in the leads and asks for a documented review.',
                'trigger' => AutomationTrigger::ISSUE_LABEL_CHANGED->value,
                'conditions' => $this->all([
                    $this->condition('label_added', 'contains', $security),
                ]),
                'actions' => [
                    $this->action('add_comment', [
                        'body' => 'Security review requested. Please document the threat model, affected data and mitigation before release.',
                    ]),
                    $this->action('notify_users', [
                        'recipients' => array_values(array_unique(array_merge($managers, $developers))),
                        'message' => 'A security issue needs review.',
                    ]),
                ],
            ];
        }

        // 5. Performance escalation.
        if ($performance) {
            $rules[] = [
                'name' => 'Escalate performance-labelled issues',
                'description' => 'Performance work always needs a baseline; ask for numbers and loop in the backend team.',
                'trigger' => AutomationTrigger::ISSUE_LABEL_CHANGED->value,
                'conditions' => $this->all([
                    $this->condition('label_added', 'contains', $performance),
                ]),
                'actions' => [
                    $this->action('add_comment', [
                        'body' => 'Please attach a before/after benchmark (p50 and p95) with the environment details.',
                    ]),
                    $this->action('notify_users', [
                        'recipients' => $developers,
                        'message' => 'A performance issue was labelled and needs a benchmark.',
                    ]),
                ],
            ];
        }

        // 6. Announce reopened issues.
        $rules[] = [
            'name' => 'Announce reopened issues',
            'description' => 'A reopened issue means the fix did not hold; flag it and give it a fresh due date.',
            'trigger' => AutomationTrigger::ISSUE_STATUS_CHANGED->value,
            'conditions' => $this->all([
                $this->condition('to_status', 'equals', 'reopened'),
            ]),
            'actions' => [
                $this->action('add_comment', [
                    'body' => 'Reopened — the previous fix did not resolve this. Please add the failing scenario and link the original PR.',
                ]),
                $this->action('set_due_date', ['offset_days' => 3]),
                $this->action('notify_users', [
                    'recipients' => array_values(array_unique(array_merge($developers, $managers))),
                    'message' => 'An issue was reopened and needs a new fix.',
                ]),
            ],
        ];

        // 7. QA hand-off when work is ready for verification.
        $rules[] = [
            'name' => 'Hand resolved issues to QA for verification',
            'description' => 'When a developer resolves an issue, the QA team is asked to verify it.',
            'trigger' => AutomationTrigger::ISSUE_STATUS_CHANGED->value,
            'conditions' => $this->all([
                $this->condition('to_status', 'equals', 'resolved'),
            ]),
            'actions' => [
                $this->action('add_comment', [
                    'body' => 'Ready for QA. Please verify on staging and attach evidence before closing.',
                ]),
                $this->action('notify_users', [
                    'recipients' => $testers,
                    'message' => 'An issue is ready for verification.',
                ]),
            ],
            'run_on_actor_changes' => true,
        ];

        // 8. Kick off review with a sensible priority.
        $rules[] = [
            'name' => 'Raise low priority work entering code review',
            'description' => 'Anything reaching code review is real work and should not stay at low priority.',
            'trigger' => AutomationTrigger::ISSUE_STATUS_CHANGED->value,
            'conditions' => $this->all([
                $this->condition('to_status', 'equals', 'code_review'),
                $this->condition('priority', 'equals', 'low'),
            ]),
            'actions' => [
                $this->action('change_priority', ['priority' => 'medium']),
            ],
        ];

        // 9. Keep technical debt visible once it moves to testing.
        if ($techDebt) {
            $rules[] = [
                'name' => 'Verify performance and tech debt before release',
                'description' => 'Tech-debt items in testing get an explicit verification reminder to avoid silent regressions.',
                'trigger' => AutomationTrigger::ISSUE_STATUS_CHANGED->value,
                'conditions' => $this->all([
                    $this->condition('to_status', 'equals', 'testing'),
                    $this->condition('labels', 'contains', $techDebt),
                ]),
                'actions' => [
                    $this->action('add_comment', [
                        'body' => 'Technical-debt change: confirm no behavioural regression and that the refactor is covered by tests.',
                    ]),
                ],
            ];
        }

        // 10. Draft rule kept disabled, as often happens in real boards.
        $rules[] = [
            'name' => 'Weekly stale issue report (draft)',
            'description' => 'Draft: nudge owners about issues that have not moved. Disabled until the report format is agreed.',
            'trigger' => AutomationTrigger::ISSUE_UPDATED->value,
            'conditions' => $this->all([
                $this->condition('status', 'in', ['in_progress', 'code_review']),
            ]),
            'actions' => [
                $this->action('notify_users', [
                    'recipients' => $managers,
                    'message' => 'Reminder: this issue has not been updated recently.',
                ]),
            ],
            'is_enabled' => false,
        ];

        return $rules;
    }

    /**
     * @param  list<array{field: string, operator: string, value?: mixed}>  $rules
     * @return array{match: string, rules: list<array<string, mixed>>}
     */
    private function all(array $rules, string $match = 'all'): array
    {
        return ['match' => $match, 'rules' => $rules];
    }

    /**
     * @return array{field: string, operator: string, value?: mixed}
     */
    private function condition(string $field, string $operator, mixed $value = null): array
    {
        $condition = ['field' => $field, 'operator' => $operator];

        if ($value !== null) {
            $condition['value'] = $value;
        }

        return $condition;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{type: string, config: array<string, mixed>}
     */
    private function action(string $type, array $config): array
    {
        return ['type' => $type, 'config' => $config];
    }

    /**
     * Seed believable execution + action history for the collected rules.
     *
     * @param  list<AutomationRule>  $rules
     */
    private function seedExecutions(array $rules): int
    {
        $issues = $this->project->issues()->get();

        if ($issues->isEmpty()) {
            return 0;
        }

        $total = 0;

        foreach ($rules as $rule) {
            if ($rule->trigger_type === AutomationTrigger::SPRINT_STARTED->value
                || $rule->trigger_type === AutomationTrigger::SPRINT_COMPLETED->value) {
                continue;
            }

            $runs = $rule->is_enabled ? rand(3, 6) : rand(1, 2);

            for ($i = 0; $i < $runs; $i++) {
                $issue = $issues->random();
                $status = $this->pickExecutionStatus($rule);

                $started = now()->subDays(rand(1, 14))->subMinutes(rand(1, 600));

                $execution = AutomationExecution::query()->create([
                    'automation_rule_id' => $rule->id,
                    'rule_version' => $rule->version,
                    'project_id' => $this->project->id,
                    'event_id' => (string) Str::uuid(),
                    'event_type' => $rule->trigger_type,
                    'subject_type' => Issue::class,
                    'subject_id' => $issue->id,
                    'status' => $status,
                    'matched_conditions' => $this->matchedConditions($rule, $status === 'skipped'),
                    'error_code' => $status === 'failed' ? 'internal_error' : null,
                    'started_at' => $started,
                    'finished_at' => $started->copy()->addSeconds(rand(1, 4)),
                ]);

                $this->seedActionExecutions($execution, $rule, $status);

                $total++;
            }
        }

        return $total;
    }

    private function pickExecutionStatus(AutomationRule $rule): string
    {
        if (! $rule->is_enabled) {
            return 'skipped';
        }

        // Mostly successful runs with the occasional skip, as in real life.
        return collect(['succeeded', 'succeeded', 'succeeded', 'skipped'])->random();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function matchedConditions(AutomationRule $rule, bool $skipped = false): array
    {
        return collect($rule->conditions['rules'] ?? [])
            ->map(fn (array $condition) => [
                'field' => $condition['field'],
                'operator' => $condition['operator'],
                'expected' => $condition['value'] ?? null,
                'passed' => ! $skipped,
            ])
            ->all();
    }

    private function seedActionExecutions(AutomationExecution $execution, AutomationRule $rule, string $status): void
    {
        foreach ($rule->actions ?? [] as $index => $actionConfig) {
            $key = "{$execution->id}:{$index}";

            AutomationActionExecution::query()->create([
                'automation_execution_id' => $execution->id,
                'action_key' => $key,
                'action_type' => $actionConfig['type'],
                'status' => $status === 'skipped' ? 'skipped' : 'succeeded',
                'attempt_count' => $status === 'skipped' ? 0 : 1,
                'idempotency_key' => $key,
                'input_summary' => $actionConfig,
                'result_summary' => $status === 'skipped' ? null : ['ok' => true],
                'started_at' => $execution->started_at,
                'finished_at' => $status === 'skipped' ? null : $execution->finished_at,
            ]);
        }
    }
}
