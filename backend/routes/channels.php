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

// Private project channel: only users who can view the project (members or
// admins). Mirrors ProjectPolicy::view.
Broadcast::channel('project.{projectId}', function (User $user, string $projectId) {
    $project = Project::find((int) $projectId);

    return $project ? $project->isVisibleTo($user) : false;
});

// Private issue channel: only users who can view the issue (project members
// or admins) may subscribe. Mirrors IssuePolicy::view.
Broadcast::channel('issue.{issueId}', function (User $user, string $issueId) {
    $issue = Issue::find((int) $issueId);

    return $issue ? $issue->isVisibleTo($user) : false;
});