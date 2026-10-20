<?php

namespace App\Services;

use App\Events\CommentCreated;
use App\Events\CommentDeleted;
use App\Events\CommentUpdated;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CommentService
{
    protected NotificationService $notificationService;

    protected ActivityLogService $activityLogService;

    public function __construct(NotificationService $notificationService, ActivityLogService $activityLogService)
    {
        $this->notificationService = $notificationService;
        $this->activityLogService = $activityLogService;
    }

    public function getCommentByIssue(Issue $issue, int $perPage = 50): LengthAwarePaginator
    {
        return $issue->comments()
            ->with('user')
            ->oldest()
            ->paginate($perPage);
    }

    public function createComment(Issue $issue, User $user, string $content): Comment
    {

        // Allow admins to comment on any issue, project members to comment on
        // issues in their project, and the issue reporter/assignee regardless
        // of membership. Mirrors the access policy of IssueController::show,
        // which doesn't restrict by membership.
        $isMember = $issue->project->members()->where('user_id', $user->id)->exists();
        $isCreatorOrAssignee = $issue->reporter_id === $user->id || $issue->assigned_to === $user->id;
        $isAdmin = $user->isAdmin();

        if (! $isMember && ! $isCreatorOrAssignee && ! $isAdmin) {
            throw new \Exception('You are not a member of this project.');
        }

        $comment = new Comment;
        $comment->issue_id = $issue->id;
        $comment->user_id = $user->id;
        $comment->comment = $content;
        $comment->save();

        event(new CommentCreated($comment->fresh(), (string) Str::uuid(), $user->id));

        $this->activityLogService->logCommentAdded($issue, $user, $content);
        $this->notificationService->sendCommentAdded($comment, $user);

        // $recipients = collect();
        // if ($issue->reporter_id && $issue->reporter_id !== $author->id) {
        //     $recipients->push($issue->reporter);
        // }
        // if ($issue->assigned_to && $issue->assigned_to !== $author->id) {
        //     $recipients->push($issue->assignee);
        // }
        // Notification::send($recipients->unique(), new SystemNotification('comment_added', $data));

        return $comment->fresh('user');

    }

    public function updateComment(Comment $comment, string $content): Comment
    {
        $oldContent = $comment->comment;
        $comment->comment = $content;
        $comment->save();

        event(new CommentUpdated($comment->fresh('user'), auth()->id()));

        $this->activityLogService->logCommentUpdated($comment->issue, auth()->user(), $oldContent, $content);

        return $comment->fresh('user');
    }

    public function deleteComment(Comment $comment): void
    {
        $content = $comment->comment;
        $issue = $comment->issue;
        $commentId = $comment->id;
        $comment->delete();

        event(new CommentDeleted($commentId, $issue->id, auth()->id()));

        $this->activityLogService->logCommentDeleted($issue, auth()->user(), $content);
    }
}
