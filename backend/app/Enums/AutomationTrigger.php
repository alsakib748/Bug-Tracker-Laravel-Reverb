<?php

namespace App\Enums;

enum AutomationTrigger: string
{
    case ISSUE_CREATED = 'issue.created';
    case ISSUE_UPDATED = 'issue.updated';
    case ISSUE_STATUS_CHANGED = 'issue.status_changed';
    case ISSUE_ASSIGNED = 'issue.assigned';
    case ISSUE_LABEL_CHANGED = 'issue.label_changed';
    case COMMENT_CREATED = 'comment.created';
    case SPRINT_STARTED = 'sprint.started';
    case SPRINT_COMPLETED = 'sprint.completed';
    case PULL_REQUEST_SYNCED = 'pull_request.synced';
    case GITHUB_PR_OPENED = 'github.pr_opened';
    case GITHUB_PR_MERGED = 'github.pr_merged';
    case GITHUB_PR_CLOSED = 'github.pr_closed';
    case GITHUB_CHECKS_FAILED = 'github.checks_failed';
    case GITHUB_COMMIT_PUSHED = 'github.commit_pushed';

    public function label(): string
    {
        return match ($this) {
            self::ISSUE_CREATED => 'Issue created',
            self::ISSUE_UPDATED => 'Issue updated',
            self::ISSUE_STATUS_CHANGED => 'Issue status changed',
            self::ISSUE_ASSIGNED => 'Issue assigned',
            self::ISSUE_LABEL_CHANGED => 'Issue label changed',
            self::COMMENT_CREATED => 'Comment created',
            self::SPRINT_STARTED => 'Sprint started',
            self::SPRINT_COMPLETED => 'Sprint completed',
            self::PULL_REQUEST_SYNCED => 'Pull request synced',
            self::GITHUB_PR_OPENED => 'GitHub pull request opened',
            self::GITHUB_PR_MERGED => 'GitHub pull request merged',
            self::GITHUB_PR_CLOSED => 'GitHub pull request closed',
            self::GITHUB_CHECKS_FAILED => 'GitHub checks failed',
            self::GITHUB_COMMIT_PUSHED => 'GitHub commit pushed',
        };
    }

    /**
     * Condition fields that make sense for this trigger (allowlist).
     *
     * @return list<string>
     */
    public function allowedConditionFields(): array
    {
        return match ($this) {
            self::ISSUE_CREATED => ['priority', 'status', 'assignee', 'type', 'reporter', 'labels'],
            self::ISSUE_UPDATED => ['priority', 'status', 'assignee', 'type', 'labels', 'due_date'],
            self::ISSUE_STATUS_CHANGED => ['from_status', 'to_status', 'priority', 'assignee', 'labels'],
            self::ISSUE_ASSIGNED => ['assignee', 'from_assignee', 'priority', 'status', 'labels'],
            self::ISSUE_LABEL_CHANGED => ['label_added', 'label_removed', 'priority', 'status'],
            self::COMMENT_CREATED => ['author', 'priority', 'status', 'labels'],
            self::SPRINT_STARTED => ['sprint_id'],
            self::SPRINT_COMPLETED => ['sprint_id'],
            self::PULL_REQUEST_SYNCED => ['priority', 'status', 'assignee', 'type', 'labels', 'pr_state', 'pr_action', 'pr_author', 'repository'],
            self::GITHUB_PR_OPENED,
            self::GITHUB_PR_MERGED,
            self::GITHUB_PR_CLOSED => ['priority', 'status', 'assignee', 'type', 'labels', 'pr_author', 'pr_target_branch', 'repository'],
            self::GITHUB_CHECKS_FAILED => ['priority', 'status', 'assignee', 'type', 'labels', 'repository'],
            self::GITHUB_COMMIT_PUSHED => ['priority', 'status', 'assignee', 'type', 'labels', 'commit_author', 'repository'],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
