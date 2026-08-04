<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CommentService
{
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

        return $comment->fresh('user');

    }

    public function updateComment(Comment $comment, string $content): Comment
    {
        $comment->comment = $content;
        $comment->save();

        return $comment->fresh('user');
    }

    public function deleteComment(Comment $comment): void
    {
        $comment->delete();
    }

}