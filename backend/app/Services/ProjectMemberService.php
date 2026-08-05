<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProjectMemberService
{

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }



    /**
     * * Get all members of a project
     */
    public function getMembers(Project $project): Collection
    {
        return $project->members()->withPivot('role', 'joined_at')->get();
    }

    /**
     * * Add a user to the project with a specific role
     */
    public function addMember(Project $project, int $userId, string $role): void
    {
        //  Check if user already a member
        if ($project->members()->where('user_id', $userId)->exists()) {
            throw new \Exception('User is already a member of this project.');
        }

        $project->members()->attach($userId, [
            'role' => $role,
            'joined_at' => now(),
        ]);

        $user = $project->members();

        $this->notificationService->sendProjectMemberAdded($project, $user, auth()->user());

    }

    /**
     * * Remove a user from the project.
     */
    public function removeMember(Project $project, int $userId): void
    {
        //  Prevent removing the creator
        if ($project->created_by === $userId) {
            throw new \Exception('Cannot remove the project creator.');
        }

        $detached = $project->members()->detach($userId);
        if ($detached === 0) {
            throw new \Exception('User is not a member of this project.');
        }
    }

    /**
     * * Get all users who are Not members of the given project.
     */

    public function getAvailableUsers(Project $project): Collection
    {
        $memberIds = $project->members()->pluck('user_id')->toArray();
        return User::whereNotIn('id', $memberIds)->get();
    }

}