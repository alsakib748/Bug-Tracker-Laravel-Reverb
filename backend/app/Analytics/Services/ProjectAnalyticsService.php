<?php

namespace App\Analytics\Services;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Queries\GitHubAnalyticsQuery;
use App\Analytics\Queries\IssueMetricsQuery;
use App\Analytics\Queries\ResolutionMetricsQuery;
use App\Analytics\Queries\WorkflowMetricsQuery;
use App\Analytics\Support\MetricDefinitions;
use Illuminate\Support\Facades\Cache;

class ProjectAnalyticsService
{
    public function __construct(
        private readonly IssueMetricsQuery $issues,
        private readonly ResolutionMetricsQuery $resolutions,
        private readonly WorkflowMetricsQuery $workflow,
        private readonly GitHubAnalyticsQuery $github,
    ) {}

    /**
     * Full overview dashboard payload.
     *
     * @return array<string, mixed>
     */
    public function overview(AnalyticsFilters $filters): array
    {
        $cacheKey = $filters->cacheKey().':overview:'.MetricDefinitions::version();

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filters) {
            return [
                'period' => [
                    'start' => $filters->periodStart->toDateString(),
                    'end' => $filters->periodEnd->toDateString(),
                    'timezone' => $filters->timezone,
                ],
                'summary' => $this->issues->summary($filters),
                'resolution' => $this->resolutions->medianResolutionHours($filters),
                'status_distribution' => $this->issues->byStatus($filters),
                'priority_distribution' => $this->issues->byPriority($filters),
                'metric_definition_version' => MetricDefinitions::version(),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function trends(AnalyticsFilters $filters, string $granularity = 'day'): array
    {
        $granularity = in_array($granularity, ['day', 'week', 'month'], true) ? $granularity : 'day';

        $cacheKey = $filters->cacheKey().":trends:{$granularity}:".MetricDefinitions::version();

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filters, $granularity) {
            return [
                'granularity' => $granularity,
                'period' => [
                    'start' => $filters->periodStart->toDateString(),
                    'end' => $filters->periodEnd->toDateString(),
                    'timezone' => $filters->timezone,
                ],
                'series' => $this->resolutions->createdVsResolved($filters, $granularity),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function workflow(AnalyticsFilters $filters): array
    {
        $cacheKey = $filters->cacheKey().':workflow:'.MetricDefinitions::version();

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filters) {
            return [
                'period' => [
                    'start' => $filters->periodStart->toDateString(),
                    'end' => $filters->periodEnd->toDateString(),
                    'timezone' => $filters->timezone,
                ],
                'time_in_status' => $this->workflow->timeInStatus($filters),
                'reopened_count' => $this->workflow->reopenedCount($filters),
            ];
        });
    }

    /**
     * GitHub development-velocity metrics (derived from stored PR/commit facts).
     *
     * @return array<string, mixed>
     */
    public function github(AnalyticsFilters $filters): array
    {
        $cacheKey = $filters->cacheKey().':github:'.MetricDefinitions::version();

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filters) {
            return [
                'period' => [
                    'start' => $filters->periodStart->toDateString(),
                    'end' => $filters->periodEnd->toDateString(),
                    'timezone' => $filters->timezone,
                ],
                'time_to_first_pr' => $this->github->timeToFirstPR($filters),
                'prs_awaiting_review' => $this->github->prsAwaitingReview($filters),
                'pr_throughput' => $this->github->prThroughput($filters),
                'pr_merge_duration' => $this->github->prMergeDuration($filters),
                'issues_without_pr' => $this->github->issuesWithoutPR($filters),
                'metric_definition_version' => MetricDefinitions::version(),
            ];
        });
    }
}
