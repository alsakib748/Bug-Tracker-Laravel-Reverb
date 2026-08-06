<?php

namespace App\Services;

use App\Events\CommentCreated;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class CommentService
{

    protected NotificationService $notificationService;
    protected ActivityLogService $activityLogService;

    public function __construct(NotificationService $notificationService, ActivityLogService $activityLogService)
    {
        $this->notificationService = $notificationService;
        $this->activityLogService = $activityLogService;
    }

    public function getCommentByIssue(Issue $issue): Collection
    {
        return $issue->comments()
            ->with('user')
            ->oldest()
            ->get();
    }

    public function createComment(Issue $issue, User $user, string $content): Comment
    {
        if (!$issue->project->members()->where('user_id', $user->id)->exists()) {
            throw new \Exception('You are not a member of this project.');
        }

        $comment = new Comment();
        $comment->issue_id = $issue->id;
        $comment->user_id = $user->id;
        $comment->comment = $content;
        $comment->save();

        event(new CommentCreated($comment));

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
        $this->activityLogService->logCommentUpdated($comment->issue, auth()->user(), $oldContent, $content);
        return $comment->fresh('user');
    }

    public function deleteComment(Comment $comment): void
    {
        $content = $comment->comment;
        $issue = $comment->issue;
        $comment->delete();
        $this->activityLogService->logCommentDeleted($issue, auth()->user(), $content);
    }

}