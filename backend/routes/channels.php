<?php

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;


// Private user channel: only the user themselves can listen.
// Laravel's broadcaster passes wildcards as strings, so cast to int.
Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return (int) $user->id === (int) $userId;
});

// Private project channel: only project members
Broadcast::channel('project.{projectId}', function (User $user, string $projectId) {
    $project = Project::find((int) $projectId);
    if (!$project) {
        return false;
    }
    return $project->members()->where('users.id', $user->id)->exists();
});

// Private issue channel: any authenticated user can subscribe (mirrors
// the controller-level access in IssueController::show, which doesn't
// restrict by project membership). Tighten this when policies are added.
Broadcast::channel('issue.{issueId}', function (User $user, string $issueId) {
    return Issue::where('id', (int) $issueId)->exists();
});