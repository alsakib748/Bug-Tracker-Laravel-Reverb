<?php

namespace App\Integrations\GitHub\Jobs;

use App\Integrations\GitHub\Services\GitHubPullRequestSyncService;
use App\Models\GitHubRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncRepositoryPullRequests implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public int $repositoryId) {}

    public function handle(GitHubPullRequestSyncService $sync): void
    {
        $repository = GitHubRepository::find($this->repositoryId);

        if (! $repository) {
            return;
        }

        $count = $sync->syncRepositoryPullRequests($repository);

        Log::info('GitHub PR backfill complete', [
            'repository' => $repository->full_name,
            'pull_requests' => $count,
        ]);
    }
}
