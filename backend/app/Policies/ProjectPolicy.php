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
        return $user->hasPermissionTo('view projects');
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('view projects')
            && $project->isVisibleTo($user);
    }

    /**
     * Only users with the create-projects permission (and admins) may create.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create projects');
    }

    /**
     * Update the project itself: requires the dedicated permission plus
     * manager/creator/admin scoping.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('update projects')
            && $project->isManagedBy($user);
    }

    /**
     * Manage a project's labels.
     */
    public function manageLabels(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('manage labels')
            && $project->isManagedBy($user);
    }

    /**
     * Manage a project's sprints.
     */
    public function manageSprints(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('manage sprints')
            && $project->isManagedBy($user);
    }

    /**
     * Manage a project's automation rules.
     */
    public function manageAutomation(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('manage automation rules')
            && $project->isManagedBy($user);
    }

    /**
     * Manage a project's GitHub integration.
     */
    public function manageGitHub(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('manage github integration')
            && $project->isManagedBy($user);
    }

    /**
     * Delete the project: permission plus admin or creator.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('delete projects')
            && ($user->isAdmin() || $project->created_by === $user->id);
    }

    /**
     * Add or remove project members.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('manage project members')
            && $project->isManagedBy($user);
    }

    /**
     * Archive the project.
     */
    public function archive(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('archive projects')
            && $project->isManagedBy($user);
    }
}
