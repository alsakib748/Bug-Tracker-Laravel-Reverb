<?php

namespace App\Services\Automation\Actions;

use App\Models\Issue;
use App\Services\IssueService;

class AssignIssueAction
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
        $userId = (int) $config['user_id'];

        if ((int) $issue->assigned_to === $userId) {
            return ['skipped' => 'already_assigned'];
        }

        $this->issueService->assignIssue($issue, $userId);

        return ['assigned_to' => $userId];
    }
}
