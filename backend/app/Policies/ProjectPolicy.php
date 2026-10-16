<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can list projects.
     * Results are always scoped by the query itself.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $project->isVisibleTo($user);
    }

    /**
     * Only administrators may create projects.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Manage (update) the project: admin, creator, or manager member.
     */
    public function update(User $user, Project $project): bool
    {
        return $project->isManagedBy($user);
    }

    /**
     * Delete the project: admin or creator.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->created_by === $user->id;
    }

    /**
     * Add or remove project members: admin, creator, or manager member.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $project->isManagedBy($user);
    }
}
