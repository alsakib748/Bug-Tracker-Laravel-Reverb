<?php

namespace App\Listeners\Automation;

use App\Enums\AutomationTrigger;
use App\Events\CommentCreated;
use App\Events\IssueAssigned;
use App\Events\IssueCreated;
use App\Events\IssueLabelsUpdated;
use App\Events\IssueStatusChanged;
use App\Events\IssueUpdated;
use App\Jobs\Automation\EvaluateAutomationRule;
use App\Models\AutomationRule;
use App\Models\Issue;
use App\Services\Automation\AutomationLoopGuard;
use Illuminate\Support\Str;

/**
 * Maps a domain event onto the automation triggers it represents, snapshots the
 * relevant context, and queues one evaluation per matching enabled rule.
 */
class DiscoverAutomationRules
{
    public function __construct(private readonly AutomationLoopGuard $loopGuard) {}

    public function handle(object $event): void
    {
        $trigger = $this->resolveTrigger($event);

        if (! $trigger) {
            return;
        }

        $issue = $this->resolveIssue($event);

        if (! $issue) {
            return;
        }

        $rules = AutomationRule::query()
            ->where('project_id', $issue->project_id)
            ->where('trigger_type', $trigger->value)
            ->where('is_enabled', true)
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

        $context = $this->buildContext($event, $trigger, $issue);

        // Changes caused by automation should not re-trigger automation unless
        // a rule explicitly opts in — this is the primary loop safeguard.
        $insideAutomation = $this->loopGuard->depth($issue->id) > 0;

        foreach ($rules as $rule) {
            if ($insideAutomation && ! $rule->run_on_actor_changes) {
                continue;
            }

            EvaluateAutomationRule::dispatch($rule->id, $context);
        }
    }

    protected function resolveTrigger(object $event): ?AutomationTrigger
    {
        return match (true) {
            $event instanceof IssueCreated => AutomationTrigger::ISSUE_CREATED,
            $event instanceof IssueUpdated => AutomationTrigger::ISSUE_UPDATED,
            $event instanceof IssueStatusChanged => AutomationTrigger::ISSUE_STATUS_CHANGED,
            $event instanceof IssueAssigned => AutomationTrigger::ISSUE_ASSIGNED,
            $event instanceof IssueLabelsUpdated => AutomationTrigger::ISSUE_LABEL_CHANGED,
            $event instanceof CommentCreated => AutomationTrigger::COMMENT_CREATED,
            default => null,
        };
    }

    protected function resolveIssue(object $event): ?Issue
    {
        if ($event instanceof CommentCreated) {
            return $event->comment->issue;
        }

        return $event->issue ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildContext(object $event, AutomationTrigger $trigger, Issue $issue): array
    {
        $labels = $issue->labels()->pluck('labels.id')->map(fn ($id) => (int) $id)->all();

        $context = [
            'event_id' => $event->eventId ?? (string) Str::uuid(),
            'event_type' => $trigger->value,
            'subject_type' => $issue::class,
            'subject_id' => $issue->id,
            'actor_id' => $event->actorId ?? auth()->id(),
            'issue' => $issue->toArray(),
            'before' => [],

            // Flattened, allowlisted condition fields.
            'priority' => $issue->priority?->value,
            'status' => $issue->status?->value,
            'assignee' => $issue->assigned_to,
            'reporter' => $issue->reporter_id,
            'type' => $issue->type?->value,
            'labels' => $labels,
            'due_date' => $issue->due_date?->toDateString(),
            'sprint_id' => $issue->sprint_id,
        ];

        if ($event instanceof IssueStatusChanged) {
            $context['from_status'] = $event->oldStatus;
            $context['to_status'] = $event->newStatus;
            $context['before']['status'] = $event->oldStatus;
        }

        if ($event instanceof IssueAssigned) {
            $context['from_assignee'] = $event->fromAssigneeId;
            $context['before']['assigned_to'] = $event->fromAssigneeId;
        }

        if ($event instanceof IssueUpdated) {
            $context['before'] = $event->before;
        }

        if ($event instanceof IssueLabelsUpdated) {
            $context['label_added'] = $event->addedLabelIds;
            $context['label_removed'] = $event->removedLabelIds;
            $context['before']['labels'] = array_values(array_unique(array_merge(
                array_diff($labels, $event->addedLabelIds),
                $event->removedLabelIds,
            )));
        }

        if ($event instanceof CommentCreated) {
            $context['author'] = $event->comment->user_id;
        }

        return $context;
    }
}
