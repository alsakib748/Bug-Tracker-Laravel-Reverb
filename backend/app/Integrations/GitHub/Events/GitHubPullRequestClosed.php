<?php

namespace App\Integrations\GitHub\Events;

use App\Models\GitHubPullRequest;
use App\Models\Issue;

/**
 * Fired when a pull request is closed without being merged.
 */
class GitHubPullRequestClosed extends GitHubAutomationEvent
{
    /**
     * @param  array<int, Issue>  $linkedIssues
     */
    public function __construct(
        public GitHubPullRequest $pullRequest,
        array $linkedIssues = [],
        ?int $actorId = null,
    ) {
        parent::__construct($linkedIssues, $actorId);
    }
}
