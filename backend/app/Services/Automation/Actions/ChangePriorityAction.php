<?php

namespace App\Services\Automation\Actions;

use App\Models\Issue;
use App\Services\IssueService;

class ChangePriorityAction
{
    public function __construct(private readonly IssueService $issueService) {}

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $issue = Issue::findOrFail($context['issue']['id']);
        $priority = (string) $config['priority'];

        if ($issue->priority?->value === $priority) {
            return ['skipped' => 'already_same_priority'];
        }

        $this->issueService->updateIssue($issue, ['priority' => $priority]);

        return ['priority' => $priority];
    }
}
