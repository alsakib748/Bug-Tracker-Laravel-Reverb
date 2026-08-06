<?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Notifications\SystemNotification;

class NotificationService
{
    private function notifyProjectMembers(Project $project, string $type, array $data): void
    {
        $project->members()
            ->get()
            ->unique('id')
            ->each(function (User $recipient) use ($type, $data) {
                $notification = $recipient->notify(new SystemNotification($type, $data));

                if ($notification) {
                    $latestNotification = $recipient->notifications()->latest()->first();
                    if ($latestNotification) {
                        event(new NotificationCreated($latestNotification));
                    }
                }
            });

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

        $member->notify(new SystemNotification('project_member_added', $data));
    }



}