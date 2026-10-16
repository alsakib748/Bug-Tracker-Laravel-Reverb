<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;

class CommentPolicy
{
    /**
     * Determine whether the user can view comments for the issue.
     */
    public function viewAny(User $user, Issue $issue): bool
    {
        return $issue->isVisibleTo($user);
    }

    /**
     * Determine whether the user can create a comment on the issue.
     */
    public function create(User $user, Issue $issue): bool
    {
        return $issue->isVisibleTo($user);
    }

    /**
     * Only the comment author or an administrator may edit a comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->isAdmin() || $comment->user_id === $user->id;
    }

    /**
     * Only the comment author or an administrator may delete a comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->isAdmin() || $comment->user_id === $user->id;
    }
}
