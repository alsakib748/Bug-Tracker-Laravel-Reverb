<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Issue;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * Determine whether the user can view/list attachments for the issue.
     */
    public function viewAny(User $user, Issue $issue): bool
    {
        return $user->hasPermissionTo('view attachments')
            && $issue->isVisibleTo($user);
    }

    /**
     * Determine whether the user can view/download a specific attachment.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        return $user->hasPermissionTo('view attachments')
            && $attachment->issue->isVisibleTo($user);
    }

    /**
     * Determine whether the user can upload an attachment to the issue.
     */
    public function create(User $user, Issue $issue): bool
    {
        return $user->hasPermissionTo('upload attachments')
            && $issue->isVisibleTo($user);
    }

    /**
     * Only the uploader or an administrator may delete an attachment.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->hasPermissionTo('delete attachments')
            && ($user->isAdmin() || $attachment->user_id === $user->id);
    }
}
