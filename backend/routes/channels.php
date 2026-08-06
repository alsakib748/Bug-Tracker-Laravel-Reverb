<?php

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

// Private user channel: only the user themselves can listen
Broadcast::channel('private-user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});

// Private project channel: only project members
Broadcast::channel('private-project.{projectId}', function (User $user, int $projectId) {
    $project = Project::find($projectId);
    return $project && $project->members->contains($user);
});

// Private issue channel: only users who can view the issue
Broadcast::channel('private-issue.{issueId}', function (User $user, int $issueId) {
    $issue = Issue::find($issueId);
    if (!$issue)
        return false;
    return $user->isAdmin() || $issue->project->members->contains($user);
});