<?php

namespace App\Integrations\GitHub\Events;

use App\Models\GitHubPullRequest;
use App\Models\Issue;

/**
 * Fired when a pull request is opened (or reopened).
 */
class GitHubPullRequestOpened extends GitHubAutomationEvent
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
