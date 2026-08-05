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
    protected ActivityLogService $activityLogService;

    public function __construct(NotificationService $notificationService, ActivityLogService $activityLogService)
    {
        $this->notificationService = $notificationService;
        $this->activityLogService = $activityLogService;
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

        $this->activityLogService->logMemberAdded($project, auth()->user(), $userId);

        $this->notificationService->sendProjectMemberAdded($project, $userId, auth()->user());

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

        // $user = $project->members();

        $detached = $project->members()->detach($userId);

        $this->activityLogService->logMemberRemoved($project, auth()->user(), $userId);

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