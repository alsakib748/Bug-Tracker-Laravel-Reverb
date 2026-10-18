<?php

namespace App\Integrations\GitHub\Events;

use App\Models\Issue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Base class for GitHub domain events that feed the automation engine. Every
 * event carries the issues it concerns plus a stable event id so downstream
 * listeners can dedupe and evaluate once per issue.
 */
abstract class GitHubAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public string $eventId;

    /**
     * @param  array<int, Issue>  $linkedIssues
     */
    public function __construct(
        public array $linkedIssues = [],
        public ?int $actorId = null,
    ) {
        $this->eventId = (string) Str::uuid();
    }
}
