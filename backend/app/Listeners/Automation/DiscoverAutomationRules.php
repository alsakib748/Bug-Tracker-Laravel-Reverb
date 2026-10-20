<?php

namespace App\Listeners\Automation;

use App\Enums\AutomationTrigger;
use App\Events\CommentCreated;
use App\Events\IssueAssigned;
use App\Events\IssueCreated;
use App\Events\IssueLabelsUpdated;
use App\Events\IssueStatusChanged;
use App\Events\IssueUpdated;
use App\Integrations\GitHub\Events\GitHubAutomationEvent;
use App\Integrations\GitHub\Events\GitHubChecksFailed;
use App\Integrations\GitHub\Events\GitHubCommitPushed;
use App\Integrations\GitHub\Events\GitHubPullRequestClosed;
use App\Integrations\GitHub\Events\GitHubPullRequestMerged;
use App\Integrations\GitHub\Events\GitHubPullRequestOpened;
use App\Integrations\GitHub\Events\PullRequestSynced;
use App\Jobs\Automation\EvaluateAutomationRule;
use App\Models\AutomationRule;
use App\Models\Issue;
use App\Services\Automation\AutomationLoopGuard;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

/**
 * Maps a domain event onto the automation triggers it represents, snapshots the
 * relevant context, and queues one evaluation per matching enabled rule.
 *
 * Queued (queue workers are already required for automation/AI): rule
 * discovery and the PR-linked issue resolution run off the request cycle.
 * Every dispatching event passes actorId explicitly, so no auth context is
 * needed in the worker; loop-guard depth is cache-based and shared across
 * processes.
 */
class DiscoverAutomationRules implements ShouldQueue
{
    public function __construct(private readonly AutomationLoopGuard $loopGuard) {}

    public function handle(object $event): void
    {
        // A PR can be linked to several issues; evaluate each one.
        if ($event instanceof PullRequestSynced) {
            $this->handlePullRequestSynced($event);

            return;
        }

        // Granular GitHub lifecycle events carry their own linked-issue list.
        if ($event instanceof GitHubAutomationEvent) {
            $this->handleGitHubEvent($event);

            return;
        }

        $trigger = $this->resolveTrigger($event);

        if (! $trigger) {
            return;
        }

        $issue = $this->resolveIssue($event);

        if (! $issue) {
            return;
        }

        $this->evaluateForIssue($trigger, $issue, $this->buildContext($event, $trigger, $issue));
    }

    /**
     * Run PR-synced rules once per issue linked to the pull request.
     */
    protected function handlePullRequestSynced(PullRequestSynced $event): void
    {
        $links = $event->pullRequest->issueLinks()->with('issue')->get();

        foreach ($links as $link) {
            if (! $link->issue) {
                continue;
            }

            $issue = $link->issue;

            $this->evaluateForIssue(
                AutomationTrigger::PULL_REQUEST_SYNCED,
                $issue,
                $this->buildContext($event, AutomationTrigger::PULL_REQUEST_SYNCED, $issue),
            );
        }
    }

    /**
     * Queue one evaluation per issue linked to a granular GitHub event.
     */
    protected function handleGitHubEvent(GitHubAutomationEvent $event): void
    {
        $trigger = $this->resolveTrigger($event);

        if (! $trigger) {
            return;
        }

        foreach ($event->linkedIssues as $issue) {
            $this->evaluateForIssue($trigger, $issue, $this->buildContext($event, $trigger, $issue));
        }
    }

    /**
     * Queue one evaluation per enabled rule matching the trigger in the issue's
     * project.
     *
     * @param  array<string, mixed>  $context
     */
    protected function evaluateForIssue(AutomationTrigger $trigger, Issue $issue, array $context): void
    {
        $rules = AutomationRule::query()
            ->where('project_id', $issue->project_id)
            ->where('trigger_type', $trigger->value)
            ->where('is_enabled', true)
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

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
            $event instanceof GitHubPullRequestOpened => AutomationTrigger::GITHUB_PR_OPENED,
            $event instanceof GitHubPullRequestMerged => AutomationTrigger::GITHUB_PR_MERGED,
            $event instanceof GitHubPullRequestClosed => AutomationTrigger::GITHUB_PR_CLOSED,
            $event instanceof GitHubChecksFailed => AutomationTrigger::GITHUB_CHECKS_FAILED,
            $event instanceof GitHubCommitPushed => AutomationTrigger::GITHUB_COMMIT_PUSHED,
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

        if ($event instanceof PullRequestSynced) {
            $pr = $event->pullRequest;

            $context['pr_state'] = $pr->is_merged ? 'merged' : $pr->state;
            $context['pr_action'] = $event->action;
            $context['pr_author'] = $pr->author_login;
            $context['repository'] = $pr->repository?->full_name;
            $context['pull_request'] = [
                'number' => $pr->number,
                'title' => $pr->title,
                'url' => $pr->html_url,
                'state' => $pr->state,
                'is_merged' => $pr->is_merged,
                'draft' => $pr->draft,
                'head_ref' => $pr->head_ref,
            ];
        }

        if ($event instanceof GitHubPullRequestOpened
            || $event instanceof GitHubPullRequestMerged
            || $event instanceof GitHubPullRequestClosed) {
            $pr = $event->pullRequest;

            $context['pr_author'] = $pr->author_login;
            $context['pr_target_branch'] = $pr->base_ref;
            $context['repository'] = $pr->repository?->full_name;
            $context['pull_request'] = [
                'number' => $pr->number,
                'title' => $pr->title,
                'url' => $pr->html_url,
                'state' => $pr->state,
                'is_merged' => $pr->is_merged,
                'draft' => $pr->draft,
                'head_ref' => $pr->head_ref,
                'base_ref' => $pr->base_ref,
                'author' => $pr->author_login,
            ];
        }

        if ($event instanceof GitHubChecksFailed) {
            $context['repository'] = $event->repository->full_name;
            $context['check'] = $event->checkRun;
        }

        if ($event instanceof GitHubCommitPushed) {
            $context['repository'] = $event->repository->full_name;
            $context['commit_author'] = $event->commits[0]['author'] ?? null;
            $context['commits'] = $event->commits;
        }

        return $context;
    }
}
