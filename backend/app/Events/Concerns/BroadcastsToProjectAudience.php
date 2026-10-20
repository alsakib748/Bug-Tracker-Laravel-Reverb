<?php

namespace App\Events\Concerns;

use App\Models\Project;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;

/**
 * Lets issue events reach every user allowed to view a project through a single
 * `user.{id}` channel each, instead of forcing clients to hold one
 * `project.{id}` channel per project.
 *
 * The audience mirrors {@see Project::isVisibleTo()}: a project's members plus
 * every admin.
 */
trait BroadcastsToProjectAudience
{
    /**
     * Per-user broadcast channels for a project's audience.
     *
     * @param  array<int, int>  $excludeUserIds  Users already covered by another channel.
     * @return array<int, PrivateChannel>
     */
    protected function projectAudienceChannels(?int $projectId, array $excludeUserIds = []): array
    {
        if (! $projectId) {
            return [];
        }

        $project = Project::query()->find($projectId);

        if (! $project) {
            return [];
        }

        $exclude = array_map('intval', $excludeUserIds);

        return $project->members()
            ->pluck('users.id')
            ->merge(User::role(User::ROLE_ADMIN)->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => in_array($id, $exclude, true))
            ->values()
            ->map(fn (int $id) => new PrivateChannel('user.'.$id))
            ->all();
    }
}
