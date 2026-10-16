<?php

namespace App\Services\Workflow;

/**
 * Canonical issue workflow definition.
 *
 * Every status change in the application must be validated against these
 * rules. The map is keyed by the current status and lists every status the
 * issue is allowed to move to next.
 */
final class IssueTransitionRules
{
    private const TRANSITIONS = [
        'open' => [
            'assigned',
            'in_progress',
            'resolved',
        ],
        'assigned' => [
            'in_progress',
            'open',
        ],
        'in_progress' => [
            'code_review',
            'open',
            'assigned',
        ],
        'code_review' => [
            'testing',
            'in_progress',
        ],
        'testing' => [
            'resolved',
            'reopened',
        ],
        'resolved' => [
            'closed',
            'reopened',
        ],
        'closed' => [
            'reopened',
        ],
        'reopened' => [
            'in_progress',
            'assigned',
            'open',
        ],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    public function allowedNextStatuses(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public function allStatuses(): array
    {
        return array_keys(self::TRANSITIONS);
    }
}
