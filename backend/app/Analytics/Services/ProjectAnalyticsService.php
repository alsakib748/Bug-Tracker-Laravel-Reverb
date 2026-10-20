<?php

namespace App\Analytics\Services;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Queries\GitHubAnalyticsQuery;
use App\Analytics\Queries\IssueMetricsQuery;
use App\Analytics\Queries\ResolutionMetricsQuery;
use App\Analytics\Queries\WorkflowMetricsQuery;
use App\Analytics\Support\MetricDefinitions;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
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
     * Cross-project dashboard payload. Reuses the same query objects and the
     * same half-open period / resolved_at semantics as overview(); the only
     * difference is the project scope, which the filters already carry.
     *
     * @return array<string, mixed>
     */
    public function dashboard(AnalyticsFilters $filters): array
    {
        $cacheKey = $filters->cacheKey('dashboard');

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filters) {
            $series = $this->resolutions->createdVsResolved($filters, 'day');

            return [
                'period' => [
                    'start' => $filters->periodStart->toDateString(),
                    'end' => $filters->periodEnd->toDateString(),
                    'timezone' => $filters->timezone,
                ],
                'scope' => [
                    'project_ids' => $filters->projectIds,
                    'key' => $filters->scopeKey(),
                ],
                'summary' => $this->issues->summary($filters),
                'resolution' => $this->resolutions->medianResolutionHours($filters),
                'status_distribution' => $this->issues->byStatus($filters),
                'priority_distribution' => $this->issues->byPriority($filters),
                'reopened_count' => $this->workflow->reopenedCount($filters),
                'trends' => [
                    'granularity' => 'day',
                    'series' => $series,
                ],
                'per_project' => $this->perProject($filters),
                'metric_definition_version' => MetricDefinitions::version(),
            ];
        });
    }

    /**
     * Lightweight per-project breakdown used for the dashboard ranking table.
     *
     * @return list<array{project_id: int, name: string, code: string, total_issues: int, open_issues: int, resolved_in_period: int}>
     */
    protected function perProject(AnalyticsFilters $filters): array
    {
        // Aggregate in SQL (GROUP BY) instead of materialising one row per
        // issue into PHP; a small per-status result set is folded in PHP.
        $counts = Issue::query()
            ->when($filters->projectIds !== [], fn ($q) => $q->whereIn('project_id', $filters->projectIds))
            ->groupBy('project_id', 'status')
            ->selectRaw('project_id, status, COUNT(*) as total')
            ->get();

        $resolved = Issue::query()
            ->when($filters->projectIds !== [], fn ($q) => $q->whereIn('project_id', $filters->projectIds))
            ->whereIn('status', MetricDefinitions::allResolvedStatuses())
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $filters->periodStart)
            ->where('resolved_at', '<', $filters->periodEnd)
            ->groupBy('project_id')
            ->selectRaw('project_id, COUNT(*) as total')
            ->pluck('total', 'project_id');

        $projectIds = $counts->pluck('project_id')->concat($resolved->keys())->unique()->all();
        $names = Project::whereIn('id', $projectIds)->get(['id', 'name', 'code'])->keyBy('id');

        return $counts
            ->groupBy('project_id')
            ->map(function ($rows, $projectId) use ($resolved, $names) {
                $open = (int) $rows->sum(function ($row) {
                    $status = $row->status instanceof IssueStatus ? $row->status->value : (string) $row->status;

                    return MetricDefinitions::isOpen($status) ? (int) $row->total : 0;
                });

                return [
                    'project_id' => (int) $projectId,
                    'name' => (string) ($names[$projectId]->name ?? ''),
                    'code' => (string) ($names[$projectId]->code ?? ''),
                    'total_issues' => (int) $rows->sum('total'),
                    'open_issues' => $open,
                    'resolved_in_period' => (int) ($resolved[$projectId] ?? 0),
                ];
            })
            ->sortByDesc('open_issues')
            ->values()
            ->all();
    }

    /**
     * Full overview dashboard payload.
     *
     * @return array<string, mixed>
     */
    public function overview(AnalyticsFilters $filters): array
    {
        $cacheKey = $filters->cacheKey('overview');

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

        $cacheKey = $filters->cacheKey("trends:{$granularity}");

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
        $cacheKey = $filters->cacheKey('workflow');

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
        $cacheKey = $filters->cacheKey('github');

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
