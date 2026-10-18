<?php

namespace App\Integrations\GitHub\Events;

use App\Models\GitHubRepository;
use App\Models\Issue;

/**
 * Fired when one or more commits are pushed to a linked repository.
 */
class GitHubCommitPushed extends GitHubAutomationEvent
{
    /**
     * @param  array<int, array{sha: string, message: string, author: ?string, url: ?string}>  $commits
     * @param  array<int, Issue>  $linkedIssues
     */
    public function __construct(
        public GitHubRepository $repository,
        public array $commits,
        array $linkedIssues = [],
        ?int $actorId = null,
    ) {
        parent::__construct($linkedIssues, $actorId);
    }
}
