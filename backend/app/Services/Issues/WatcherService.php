<?php

namespace App\Services\Issues;

use App\Events\IssueWatchersUpdated;
use App\Models\Issue;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Who is watching an issue.
 */
final class WatcherService
{
    public function __construct(
        private readonly ActivityLogService $activityLogs,
    ) {
    }

    /**
     * @return Collection<int, User>
     */
    public function listForIssue(Issue $issue): Collection
    {
        return $issue->watchers()->orderBy('users.name')->get();
    }

    public function isWatching(Issue $issue, User $user): bool
    {
        return $issue->watchers()->where('users.id', $user->id)->exists();
    }

    public function watch(Issue $issue, User $user): void
    {
        $result = $issue->watchers()->syncWithoutDetaching([
            $user->id => ['created_at' => now()],
        ]);

        if (! empty($result['attached'])) {
            $this->activityLogs->logIssueWatcherAdded($issue, $user);
            $this->broadcast($issue);
        }
    }

    public function unwatch(Issue $issue, User $user): void
    {
        $detached = $issue->watchers()->detach($user->id);

        if ($detached) {
            $this->activityLogs->logIssueWatcherRemoved($issue, $user);
            $this->broadcast($issue);
        }
    }

    private function broadcast(Issue $issue): void
    {
        $watcherIds = $issue->watchers()->pluck('users.id')->all();

        DB::afterCommit(function () use ($issue, $watcherIds) {
            event(new IssueWatchersUpdated($issue, $watcherIds));
        });
    }
}
