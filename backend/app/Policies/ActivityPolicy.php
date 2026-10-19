<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view activity logs');
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $project = $activityLog->project ?? $activityLog->issue?->project;

        return $project !== null && $project->isVisibleTo($user);
    }
}
