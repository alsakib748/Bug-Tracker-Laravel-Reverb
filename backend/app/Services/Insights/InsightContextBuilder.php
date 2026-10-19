<?php

namespace App\Services\Insights;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Services\ProjectAnalyticsService;

class InsightContextBuilder
{
    public function __construct(
        private readonly ProjectAnalyticsService $analytics,
    ) {}

    /**
     * Assemble the evidence package handed to the AI agent. Only deterministic
     * metrics produced by the analytics layer are included.
     *
     * @return array<string, mixed>
     */
    public function build(AnalyticsFilters $filters, string $insightType): array
    {
        $overview = $this->analytics->overview($filters);
        $workflow = $this->analytics->workflow($filters);
        $trends = $this->analytics->trends($filters, 'week');
        $github = $this->analytics->github($filters);

        return [
            'project_scope' => $filters->scopeKey(),
            'project_ids' => $filters->projectIds,
            'insight_type' => $insightType,
            'period' => [
                'start' => $filters->periodStart->toDateString(),
                'end' => $filters->periodEnd->toDateString(),
            ],
            'metrics' => [
                'summary' => $overview['summary'],
                'resolution' => $overview['resolution'],
                'status_distribution' => $overview['status_distribution'],
                'priority_distribution' => $overview['priority_distribution'],
                'workflow' => $workflow,
                'trends_weekly' => $trends['series'],
                'github' => $github,
            ],
            'data_quality' => [
                'resolution_coverage' => $overview['resolution']['coverage'] ?? 'unknown',
                'metric_definition_version' => $overview['metric_definition_version'] ?? null,
                'github_data_available' => ($github['time_to_first_pr']['sample_size'] ?? 0) > 0
                    || ($github['pr_throughput']['buckets'] ?? []) !== [],
            ],
        ];
    }
}
