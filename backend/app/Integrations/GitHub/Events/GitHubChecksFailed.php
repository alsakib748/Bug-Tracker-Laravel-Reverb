<?php

namespace App\Integrations\GitHub\Events;

use App\Models\GitHubRepository;
use App\Models\Issue;

/**
 * Fired when a repository check run completes with a failing conclusion.
 */
class GitHubChecksFailed extends GitHubAutomationEvent
{
    /**
     * @param  array<string, mixed>  $checkRun
     * @param  array<int, Issue>  $linkedIssues
     */
    public function __construct(
        public GitHubRepository $repository,
        public array $checkRun,
        array $linkedIssues = [],
        ?int $actorId = null,
    ) {
        parent::__construct($linkedIssues, $actorId);
    }
}
