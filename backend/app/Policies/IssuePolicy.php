<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;

class IssuePolicy
{
    /**
     * Results are always scoped by the query itself.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view issues');
    }

    /**
     * Determine whether the user can view the issue.
     */
    public function view(User $user, Issue $issue): bool
    {
        return $user->hasPermissionTo('view issues')
            && $issue->isVisibleTo($user);
    }

    /**
     * Determine whether the user can create an issue in the given project.
     */
    public function create(User $user, ?Project $project = null): bool
    {
        if (! $user->hasPermissionTo('create issues')) {
            return false;
        }

        return $project === null || $project->isVisibleTo($user);
    }

    public function update(User $user, Issue $issue): bool
    {
        return $user->hasPermissionTo('update issues')
            && $issue->isVisibleTo($user);
    }

    public function delete(User $user, Issue $issue): bool
    {
        return $user->hasPermissionTo('delete issues')
            && ($user->isAdmin() || $issue->reporter_id === $user->id || $issue->isVisibleTo($user));
    }

    public function assign(User $user, Issue $issue): bool
    {
        return $user->hasPermissionTo('assign issues')
            && $issue->isVisibleTo($user);
    }

    public function changeStatus(User $user, Issue $issue): bool
    {
        return $user->hasPermissionTo('change issue status')
            && $issue->isVisibleTo($user);
    }

    public function reopen(User $user, Issue $issue): bool
    {
        return $user->hasPermissionTo('reopen issues')
            && $issue->isVisibleTo($user);
    }

    public function close(User $user, Issue $issue): bool
    {
        return $user->hasPermissionTo('close issues')
            && $issue->isVisibleTo($user);
    }
}
