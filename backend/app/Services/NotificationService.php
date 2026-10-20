<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Persist a database notification and broadcast it.
     *
     * The row is created explicitly (instead of notify() + refetch) so the
     * broadcast always carries the exact record that was just written, even
     * under concurrent writes for the same recipient.
     */
    private function notifyUser(User $recipient, string $type, array $data): void
    {
        $notification = new SystemNotification($type, $data);

        $record = $recipient->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => SystemNotification::class,
            'data' => $notification->toDatabase($recipient),
            'read_at' => null,
        ]);

        event(new NotificationCreated($record));
    }

    private function notifyProjectMembers(Project $project, string $type, array $data, ?int $excludeUserId = null): void
    {
        $recipients = $project->members()
            ->get()
            ->unique('id')
            ->reject(fn (User $recipient) => $excludeUserId !== null && $recipient->id === $excludeUserId);

        if ($recipients->isEmpty()) {
            return;
        }

        $now = now();

        // Build every row in memory, then persist them with a single bulk
        // INSERT instead of one query per member. toDatabase() ignores the
        // recipient, so the payload is identical across rows; only the
        // notifiable (and the generated id) differ.
        $records = $recipients->map(function (User $recipient) use ($type, $data, $now) {
            $record = new DatabaseNotification();
            $record->forceFill([
                'id' => (string) Str::uuid(),
                'notifiable_type' => $recipient->getMorphClass(),
                'notifiable_id' => $recipient->getKey(),
                'type' => SystemNotification::class,
                'data' => (new SystemNotification($type, $data))->toDatabase($recipient),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return $record;
        });

        DB::table('notifications')->insert(
            $records->map(fn (DatabaseNotification $record) => $record->getAttributes())->all()
        );

        $records->each(fn (DatabaseNotification $record) => event(new NotificationCreated($record)));
    }

    /**
     * Send notification when a new issue is created.
     * Notify the project members except the creator.
     */
    public function sendIssueCreated(Issue $issue, User $createdBy): void
    {
        if (!$issue->project) {
            return;
        }

        $data = [
            'issue_id' => $issue->id,
            'project_id' => $issue->project_id,
            'title' => $issue->title,
            'created_by' => $createdBy->name,
            'url' => '/issues/' . $issue->id,
        ];

        $this->notifyProjectMembers($issue->project, 'issue_created', $data);
    }

    /**
     * Send notification when an issue is assigned.
     */

    public function sendIssueAssigned(Issue $issue, User $assignee, User $assignedBy): void
    {
        $data = [
            'issue_id' => $issue->id,
            'project_id' => $issue->project_id,
            'title' => $issue->title,
            'assigned_by' => $assignedBy->name,
            'url' => '/issues/' . $issue->id,
        ];

        $this->notifyProjectMembers($issue->project, 'issue_assigned', $data);

    }

    /**
     * Send notification when a comment is added.
     * Notify the issue reporter and the assigned developer, but not the comment author.
     */
    public function sendCommentAdded(Comment $comment, User $author): void
    {
        $issue = $comment->issue;
        if (!$issue->project) {
            return;
        }

        $data = [
            'issue_id' => $issue->id,
            'project_id' => $issue->project_id,
            'title' => $issue->title,
            'comment_author' => $author->name,
            'comment_snippet' => substr($comment->comment, 0, 100) . (strlen($comment->comment) > 100 ? '...' : ''),
            'url' => '/issues/' . $issue->id,
        ];

        $this->notifyProjectMembers($issue->project, 'comment_added', $data);

    }

    /**
     * Send notification when issue status changes.
     * Notify reporter and assignee (if different).
     */

    public function sendIssueStatusChanged(Issue $issue, string $oldStatus, string $newStatus, User $changedBy): void
    {
        if (!$issue->project) {
            return;
        }

        $data = [
            'issue_id' => $issue->id,
            'project_id' => $issue->project_id,
            'title' => $issue->title,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy->name,
            'url' => '/issues/' . $issue->id,
        ];

        $this->notifyProjectMembers($issue->project, 'status_changed', $data);

    }

    /**
     * Send notification when a user is added to a project.
     */

    public function sendProjectMemberAdded(Project $project, User $member, User $addedBy): void
    {
        $data = [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'added_by' => $addedBy->name,
            'url' => '/projects/' . $project->id,
        ];

        $this->notifyUser($member, 'project_member_added', $data);

    }

}