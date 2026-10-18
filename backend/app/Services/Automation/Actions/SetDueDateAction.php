<?php

namespace App\Services\Automation\Actions;

use App\Models\Issue;
use App\Services\IssueService;

class SetDueDateAction
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
        $newDueDate = now()->addDays((int) $config['offset_days']);

        $this->issueService->updateIssue($issue, [
            'due_date' => $newDueDate->toDateString(),
        ]);

        return ['due_date' => $newDueDate->toDateString()];
    }
}
