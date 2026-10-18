<?php

namespace App\Services\Automation;

use App\Enums\AutomationExecutionStatus;
use App\Models\AutomationActionExecution;
use App\Models\AutomationExecution;
use App\Models\AutomationRule;
use App\Models\User;
use App\Services\Automation\Actions\AddCommentAction;
use App\Services\Automation\Actions\AssignIssueAction;
use App\Services\Automation\Actions\ChangePriorityAction;
use App\Services\Automation\Actions\ManageLabelAction;
use App\Services\Automation\Actions\NotifyUsersAction;
use App\Services\Automation\Actions\SetDueDateAction;
use Illuminate\Support\Facades\Log;

/**
 * Deterministic, durable rule executor.
 *
 * Every run is persisted as an {@see AutomationExecution} (deduplicated per
 * rule + event + version) and every action as an {@see AutomationActionExecution}
 * so partial failures stay visible and replaying an event is safe.
 */
class AutomationExecutionService
{
    public function __construct(
        private readonly AutomationConditionEvaluator $evaluator,
        private readonly AutomationLoopGuard $loopGuard,
        private readonly AssignIssueAction $assignAction,
        private readonly ChangePriorityAction $priorityAction,
        private readonly ManageLabelAction $labelAction,
        private readonly AddCommentAction $commentAction,
        private readonly NotifyUsersAction $notifyAction,
        private readonly SetDueDateAction $dueDateAction,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(AutomationRule $rule, array $context): AutomationExecution
    {
        $execution = AutomationExecution::create([
            'automation_rule_id' => $rule->id,
            'rule_version' => $rule->version,
            'project_id' => $rule->project_id,
            'event_id' => $context['event_id'],
            'event_type' => $context['event_type'],
            'subject_type' => $context['subject_type'] ?? null,
            'subject_id' => $context['subject_id'] ?? null,
            'status' => AutomationExecutionStatus::RUNNING->value,
            'started_at' => now(),
        ]);

        $issueId = $context['issue']['id'] ?? null;
        $entered = false;

        try {
            if ($issueId && $this->loopGuard->exceeds($issueId)) {
                $execution->update([
                    'status' => AutomationExecutionStatus::SKIPPED->value,
                    'error_code' => 'loop_guard_exceeded',
                    'finished_at' => now(),
                ]);

                return $execution;
            }

            if ($issueId) {
                $this->loopGuard->enter($issueId);
                $entered = true;
            }

            $evaluated = $this->evaluator->evaluate($rule->conditions ?? [], $context);

            if (! $evaluated['matched']) {
                $execution->update([
                    'status' => AutomationExecutionStatus::SKIPPED->value,
                    'matched_conditions' => $evaluated['evaluated'] ?? [],
                    'finished_at' => now(),
                ]);

                return $execution;
            }

            $anyFailed = false;

            $this->withActor($rule, $context, function () use ($rule, $context, $execution, &$anyFailed) {
                foreach ($rule->actions ?? [] as $index => $actionConfig) {
                    $result = $this->runAction($execution, (int) $index, $actionConfig, $context);

                    if ($result['status'] === 'failed') {
                        $anyFailed = true;
                    }
                }
            });

            $execution->update([
                'status' => $anyFailed
                    ? AutomationExecutionStatus::PARTIALLY_FAILED->value
                    : AutomationExecutionStatus::SUCCEEDED->value,
                'matched_conditions' => $evaluated['evaluated'] ?? [],
                'finished_at' => now(),
            ]);

            return $execution;
        } catch (\Throwable $e) {
            Log::error('Automation execution failed', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);

            $execution->update([
                'status' => AutomationExecutionStatus::FAILED->value,
                'error_code' => 'internal_error',
                'finished_at' => now(),
            ]);

            return $execution;
        } finally {
            if ($entered && $issueId) {
                $this->loopGuard->exit($issueId);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $actionConfig
     * @param  array<string, mixed>  $context
     * @return array{status: string, result?: array<string, mixed>, error?: string}
     */
    protected function runAction(
        AutomationExecution $execution,
        int $index,
        array $actionConfig,
        array $context,
    ): array {
        $actionKey = "{$execution->id}:{$index}";

        $actionExecution = AutomationActionExecution::firstOrCreate(
            ['idempotency_key' => $actionKey],
            [
                'automation_execution_id' => $execution->id,
                'action_key' => $actionKey,
                'action_type' => $actionConfig['type'],
                'status' => 'running',
                'attempt_count' => 1,
                'input_summary' => $actionConfig,
                'started_at' => now(),
            ]
        );

        if ($actionExecution->status === 'succeeded') {
            return ['status' => 'succeeded', 'result' => $actionExecution->result_summary ?? []];
        }

        $config = $actionConfig['config'] ?? [];
        // The action type is needed by handlers that share a config shape
        // (e.g. add_label / remove_label).
        $config['_action_type'] = $actionConfig['type'];

        try {
            $handler = $this->resolveHandler($actionConfig['type']);
            $result = $handler->execute($config, $context);

            $actionExecution->update([
                'status' => 'succeeded',
                'result_summary' => $result,
                'attempt_count' => $actionExecution->attempt_count + 1,
                'finished_at' => now(),
            ]);

            return ['status' => 'succeeded', 'result' => $result];
        } catch (\Throwable $e) {
            $actionExecution->update([
                'status' => 'failed',
                'result_summary' => ['error' => $e->getMessage()],
                'error_code' => 'action_failed',
                'attempt_count' => $actionExecution->attempt_count + 1,
                'finished_at' => now(),
            ]);

            Log::warning('Automation action failed', [
                'execution_id' => $execution->id,
                'action_type' => $actionConfig['type'],
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    protected function resolveHandler(string $type): object
    {
        return match ($type) {
            'assign_issue' => $this->assignAction,
            'change_priority' => $this->priorityAction,
            'add_label', 'remove_label' => $this->labelAction,
            'add_comment' => $this->commentAction,
            'notify_users' => $this->notifyAction,
            'set_due_date' => $this->dueDateAction,
            default => throw new \RuntimeException("No handler for action type: {$type}"),
        };
    }

    /**
     * Run a callback while the resolved actor is the authenticated user so the
     * reused domain services keep attributing the change correctly (queued jobs
     * have no request-scoped user).
     */
    protected function withActor(AutomationRule $rule, array $context, callable $callback): void
    {
        $actor = $this->resolveActor($rule, $context);

        if (! $actor) {
            $callback();

            return;
        }

        $previous = auth()->user();
        auth()->setUser($actor);

        try {
            $callback();
        } finally {
            if ($previous) {
                auth()->setUser($previous);
            } else {
                auth()->forgetUser();
            }
        }
    }

    protected function resolveActor(AutomationRule $rule, array $context): ?User
    {
        $candidates = array_filter([
            $context['actor_id'] ?? null,
            $rule->created_by,
            $context['issue']['reporter_id'] ?? null,
        ]);

        foreach ($candidates as $id) {
            $user = User::find($id);

            if ($user) {
                return $user;
            }
        }

        return User::query()->where('role', 'admin')->first() ?? User::query()->first();
    }
}
