<?php

namespace App\Integrations\GitHub\Events;

use App\Models\GitHubPullRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever a GitHub PR is upserted from a webhook. Extension point for
 * automation rules, notifications, etc.
 */
class PullRequestSynced
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public GitHubPullRequest $pullRequest,
        public string $action,
    ) {}
}
