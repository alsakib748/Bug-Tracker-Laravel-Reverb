<?php

namespace App\Services\Ai;

use App\Enums\IssueType;
use App\Enums\Priority;
use App\Enums\Severity;
use App\Models\Issue;
use App\Models\Project;

class BugReportContextBuilder
{
    /**
     * Fields persisted on the issue that the analysis fingerprint is built
     * from, so a stored analysis can be detected as stale once they change.
     *
     * @var list<string>
     */
    protected const FINGERPRINT_FIELDS = [
        'title',
        'description',
    ];

    /**
     * Build the minimal, safe context handed to the AI agent.
     *
     * @return array<string, mixed>
     */
    public function build(array $input, Project $project): array
    {
        $availableLabels = $project->labels()
            ->select(['id', 'name', 'description'])
            ->orderBy('name')
            ->get()
            ->map(fn ($label) => [
                'id' => $label->id,
                'name' => $label->name,
                'description' => $label->description,
            ])
            ->values()
            ->all();

        return [
            'report' => [
                'title' => (string) ($input['title'] ?? ''),
                'description' => (string) ($input['description'] ?? ''),
                'type' => $input['type'] ?? null,
                'severity' => $input['severity'] ?? null,
                'current_priority' => $input['priority'] ?? null,
                'expected_behavior' => (string) ($input['expected_behavior'] ?? ''),
                'actual_behavior' => (string) ($input['actual_behavior'] ?? ''),
                'reproduction_steps' => $this->normalizeSteps($input['reproduction_steps'] ?? []),
                'environment' => (string) ($input['environment'] ?? ''),
            ],
            'allowed_priorities' => array_column(Priority::cases(), 'value'),
            'allowed_types' => array_column(IssueType::cases(), 'value'),
            'allowed_severities' => array_column(Severity::cases(), 'value'),
            'available_labels' => $availableLabels,
        ];
    }

    /**
     * Deterministically fingerprint the analyzed input so a stored analysis
     * can be checked for staleness against the current issue.
     */
    public function hashContext(array $input): string
    {
        return $this->fingerprint([
            'title' => $input['title'] ?? '',
            'description' => $input['description'] ?? '',
        ]);
    }

    /**
     * Fingerprint the persisted, comparable fields of an issue.
     */
    public function hashIssue(Issue $issue): string
    {
        return $this->fingerprint([
            'title' => $issue->title,
            'description' => $issue->description,
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function fingerprint(array $values): string
    {
        $normalized = [];

        foreach (self::FINGERPRINT_FIELDS as $field) {
            $normalized[$field] = trim((string) ($values[$field] ?? ''));
        }

        return hash('sha256', json_encode($normalized));
    }

    /**
     * @return list<string>
     */
    protected function normalizeSteps(mixed $steps): array
    {
        if (! is_array($steps)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($step) => is_string($step) ? trim($step) : '',
            $steps,
        ), fn ($step) => $step !== ''));
    }
}
