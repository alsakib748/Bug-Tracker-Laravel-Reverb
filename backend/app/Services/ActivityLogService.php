<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Generic log method.
     */
    public function log(
        ActivityAction $action,
        ?User $user = null,
        ?Project $project = null,
        ?Issue $issue = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        ?array $properties = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): ActivityLog {
        $user = $user ?? Auth::user();
        $ip = $ip ?? request()->ip();
        $userAgent = $userAgent ?? request()->userAgent();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'project_id' => $project?->id,
            'issue_id' => $issue?->id,
            'action' => $action->value,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'properties' => $properties,
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    // ---- Convenience helpers ----

    public function logProjectCreated(Project $project, User $user): void
    {
        $this->log(
            ActivityAction::PROJECT_CREATED,
            $user,
            $project,
            null,
            Project::class,
            $project->id,
            "{$user->name} created project '{$project->name}'",
            ['project_code' => $project->code]
        );
    }

    public function logProjectUpdated(Project $project, User $user, array $oldAttributes, array $newAttributes): void
    {
        $this->log(
            ActivityAction::PROJECT_UPDATED,
            $user,
            $project,
            null,
            Project::class,
            $project->id,
            "{$user->name} updated project '{$project->name}'",
            ['old' => $oldAttributes, 'new' => $newAttributes]
        );
    }

    public function logMemberAdded(Project $project, User $addedBy, User $newMember): void
    {
        $this->log(
            ActivityAction::MEMBER_ADDED,
            $addedBy,
            $project,
            null,
            User::class,
            $newMember->id,
            "{$addedBy->name} added {$newMember->name} to project '{$project->name}'",
            ['member_id' => $newMember->id, 'member_name' => $newMember->name]
        );
    }

    public function logMemberRemoved(Project $project, User $removedBy, User $removedMember): void
    {
        $this->log(
            ActivityAction::MEMBER_REMOVED,
            $removedBy,
            $project,
            null,
            User::class,
            $removedMember->id,
            "{$removedBy->name} removed {$removedMember->name} from project '{$project->name}'",
            ['member_id' => $removedMember->id, 'member_name' => $removedMember->name]
        );
    }

    public function logIssueCreated(Issue $issue, User $reporter): void
    {
        $this->log(
            ActivityAction::ISSUE_CREATED,
            $reporter,
            $issue->project,
            $issue,
            Issue::class,
            $issue->id,
            "{$reporter->name} created issue #{$issue->id}: {$issue->title}",
            ['issue_title' => $issue->title, 'priority' => $issue->priority]
        );
    }

    public function logIssueUpdated(Issue $issue, User $user, array $oldAttributes, array $newAttributes): void
    {
        $this->log(
            ActivityAction::ISSUE_UPDATED,
            $user,
            $issue->project,
            $issue,
            Issue::class,
            $issue->id,
            "{$user->name} updated issue #{$issue->id}",
            ['old' => $oldAttributes, 'new' => $newAttributes]
        );
    }

    public function logIssueAssigned(Issue $issue, User $assignedBy, User $assignee): void
    {
        $this->log(
            ActivityAction::ISSUE_ASSIGNED,
            $assignedBy,
            $issue->project,
            $issue,
            User::class,
            $assignee->id,
            "{$assignedBy->name} assigned issue #{$issue->id} to {$assignee->name}",
            ['assignee_id' => $assignee->id, 'assignee_name' => $assignee->name]
        );
    }

    public function logIssueStatusChanged(Issue $issue, User $changedBy, string $oldStatus, string $newStatus): void
    {
        $this->log(
            ActivityAction::ISSUE_STATUS_CHANGED,
            $changedBy,
            $issue->project,
            $issue,
            Issue::class,
            $issue->id,
            "{$changedBy->name} changed status of issue #{$issue->id} from {$oldStatus} to {$newStatus}",
            ['old_status' => $oldStatus, 'new_status' => $newStatus]
        );
    }

    public function logIssueClosed(Issue $issue, User $closedBy): void
    {
        $this->log(
            ActivityAction::ISSUE_CLOSED,
            $closedBy,
            $issue->project,
            $issue,
            Issue::class,
            $issue->id,
            "{$closedBy->name} closed issue #{$issue->id}",
        );
    }

    public function logIssueReopened(Issue $issue, User $reopenedBy): void
    {
        $this->log(
            ActivityAction::ISSUE_REOPENED,
            $reopenedBy,
            $issue->project,
            $issue,
            Issue::class,
            $issue->id,
            "{$reopenedBy->name} reopened issue #{$issue->id}",
        );
    }

    public function logCommentAdded(Issue $issue, User $user, string $comment): void
    {
        $this->log(
            ActivityAction::COMMENT_ADDED,
            $user,
            $issue->project,
            $issue,
            null,
            null,
            "{$user->name} commented on issue #{$issue->id}",
            ['comment_snippet' => substr($comment, 0, 100)]
        );
    }

    public function logCommentUpdated(Issue $issue, User $user, string $oldComment, string $newComment): void
    {
        $this->log(
            ActivityAction::COMMENT_UPDATED,
            $user,
            $issue->project,
            $issue,
            null,
            null,
            "{$user->name} updated a comment on issue #{$issue->id}",
            ['old_comment' => $oldComment, 'new_comment' => $newComment]
        );
    }

    public function logCommentDeleted(Issue $issue, User $user, string $deletedComment): void
    {
        $this->log(
            ActivityAction::COMMENT_DELETED,
            $user,
            $issue->project,
            $issue,
            null,
            null,
            "{$user->name} deleted a comment on issue #{$issue->id}",
            ['deleted_comment' => $deletedComment]
        );
    }
}
