<?php

namespace App\Analytics\Queries;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Support\MetricDefinitions;
use App\Analytics\Support\SqlDialect;
use Illuminate\Support\Facades\DB;

/**
 * Development-velocity metrics derived from the stored GitHub facts
 * (pull requests, commits and their issue links). These are pure counts and
 * durations — interpretation happens in the AI insight layer, never here.
 *
 * Durations are computed in SQL (driver-aware) and throughput is aggregated
 * per day in SQL, then folded into ISO week labels in PHP so the metrics
 * behave identically on MySQL and SQLite.
 */
class GitHubAnalyticsQuery
{
    /**
     * Scope a query builder to the projects in the filters.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $column
     * @return \Illuminate\Database\Query\Builder
     */
    protected function scopeProjects($query, AnalyticsFilters $filters, string $column)
    {
        return $filters->projectIds !== []
            ? $query->whereIn($column, $filters->projectIds)
            : $query;
    }

    /**
     * Hours between an issue's creation and the first linked PR being opened.
     *
     * @return array{median_hours: float|null, sample_size: int, coverage: string}
     */
    public function timeToFirstPR(AnalyticsFilters $filters): array
    {
        $durations = $this->scopeProjects(DB::table('issues'), $filters, 'issues.project_id')
            ->join('github_issue_links', 'github_issue_links.issue_id', '=', 'issues.id')
            ->join('github_pull_requests', function ($join) {
                $join->on('github_pull_requests.github_repository_id', '=', 'github_issue_links.github_repository_id')
                    ->on('github_pull_requests.github_pr_id', '=', 'github_issue_links.github_entity_id');
            })
            ->where('github_issue_links.entity_type', 'pull_request')
            ->whereNotNull('github_pull_requests.opened_at')
            ->where('issues.created_at', '>=', $filters->periodStart)
            ->where('issues.created_at', '<', $filters->periodEnd)
            ->selectRaw(SqlDialect::secondsBetween('issues.created_at', 'github_pull_requests.opened_at').' as seconds')
            ->pluck('seconds')
            ->filter(fn ($seconds) => $seconds !== null)
            ->map(fn ($seconds) => (float) $seconds / 3600)
            ->filter(fn ($hours) => $hours >= 0)
            ->sort()
            ->values();

        if ($durations->isEmpty()) {
            return ['median_hours' => null, 'sample_size' => 0, 'coverage' => 'none'];
        }

        return [
            'median_hours' => round($this->median($durations->all()), 1),
            'sample_size' => $durations->count(),
            'coverage' => 'complete',
        ];
    }

    /**
     * Open, non-draft PRs older than the review threshold in linked repos.
     *
     * @return array{count: int, threshold_hours: int}
     */
    public function prsAwaitingReview(AnalyticsFilters $filters, int $thresholdHours = 24): array
    {
        $threshold = now()->subHours($thresholdHours);

        $count = $this->scopeProjects(
            DB::table('github_pull_requests')
                ->join('github_project_repositories', 'github_project_repositories.github_repository_id', '=', 'github_pull_requests.github_repository_id'),
            $filters,
            'github_project_repositories.project_id'
        )
            ->where('github_pull_requests.state', 'open')
            ->where('github_pull_requests.draft', false)
            ->whereNotNull('github_pull_requests.opened_at')
            ->where('github_pull_requests.opened_at', '<', $threshold)
            ->count();

        return [
            'count' => $count,
            'threshold_hours' => $thresholdHours,
        ];
    }

    /**
     * Opened vs merged pull requests per ISO week.
     *
     * @return array{buckets: list<string>, opened: list<int>, merged: list<int>}
     */
    public function prThroughput(AnalyticsFilters $filters): array
    {
        $opened = $this->prDailyCounts(
            $filters,
            'opened_at',
            fn ($query) => $query
                ->where('github_pull_requests.opened_at', '>=', $filters->periodStart)
                ->where('github_pull_requests.opened_at', '<', $filters->periodEnd),
        );

        $merged = $this->prDailyCounts(
            $filters,
            'merged_at',
            fn ($query) => $query
                ->whereNotNull('github_pull_requests.merged_at')
                ->where('github_pull_requests.merged_at', '>=', $filters->periodStart)
                ->where('github_pull_requests.merged_at', '<', $filters->periodEnd),
        );

        $openedBuckets = SqlDialect::bucketDays($opened, 'week');
        $mergedBuckets = SqlDialect::bucketDays($merged, 'week');

        $buckets = collect(array_keys($openedBuckets))
            ->merge(array_keys($mergedBuckets))
            ->unique()
            ->sort()
            ->values();

        return [
            'buckets' => $buckets->all(),
            'opened' => $buckets->map(fn ($bucket) => (int) ($openedBuckets[$bucket] ?? 0))->all(),
            'merged' => $buckets->map(fn ($bucket) => (int) ($mergedBuckets[$bucket] ?? 0))->all(),
        ];
    }

    /**
     * Median pull request duration (opened -> merged) in hours.
     *
     * @return array{median_hours: float|null, sample_size: int}
     */
    public function prMergeDuration(AnalyticsFilters $filters): array
    {
        $durations = $this->prQuery($filters)
            ->whereNotNull('github_pull_requests.merged_at')
            ->whereNotNull('github_pull_requests.opened_at')
            ->where('github_pull_requests.merged_at', '>=', $filters->periodStart)
            ->where('github_pull_requests.merged_at', '<', $filters->periodEnd)
            ->selectRaw(SqlDialect::secondsBetween('github_pull_requests.opened_at', 'github_pull_requests.merged_at').' as seconds')
            ->pluck('seconds')
            ->filter(fn ($seconds) => $seconds !== null)
            ->map(fn ($seconds) => (float) $seconds / 3600)
            ->filter(fn ($hours) => $hours >= 0)
            ->sort()
            ->values();

        if ($durations->isEmpty()) {
            return ['median_hours' => null, 'sample_size' => 0];
        }

        return [
            'median_hours' => round($this->median($durations->all()), 1),
            'sample_size' => $durations->count(),
        ];
    }

    /**
     * Open issues that have no linked pull request yet ("not started").
     */
    public function issuesWithoutPR(AnalyticsFilters $filters): int
    {
        return $this->scopeProjects(DB::table('issues'), $filters, 'issues.project_id')
            ->leftJoin('github_issue_links', function ($join) {
                $join->on('github_issue_links.issue_id', '=', 'issues.id')
                    ->where('github_issue_links.entity_type', 'pull_request');
            })
            ->whereIn('issues.status', MetricDefinitions::allOpenStatuses())
            ->whereNull('github_issue_links.id')
            ->count();
    }

    /**
     * Base query over pull requests, scoped to the filtered projects.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function prQuery(AnalyticsFilters $filters)
    {
        return $this->scopeProjects(
            DB::table('github_pull_requests')
                ->join('github_project_repositories', 'github_project_repositories.github_repository_id', '=', 'github_pull_requests.github_repository_id'),
            $filters,
            'github_project_repositories.project_id',
        );
    }

    /**
     * Daily counts of a pull request timestamp column, keyed by "Y-m-d".
     *
     * @param  callable(\Illuminate\Database\Query\Builder): \Illuminate\Database\Query\Builder  $constrain
     * @return array<string, int>
     */
    protected function prDailyCounts(AnalyticsFilters $filters, string $column, callable $constrain): array
    {
        $query = $this->prQuery($filters);
        $query = $constrain($query);

        $counts = [];

        foreach ($query
            ->selectRaw(SqlDialect::date($column).' as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->get() as $row) {
            $counts[(string) $row->bucket] = (int) $row->total;
        }

        return $counts;
    }

    /**
     * @param  array<int, int|float|string>  $values
     */
    protected function median(array $values): float
    {
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        if ($count % 2 === 0) {
            return ((float) $values[$count / 2 - 1] + (float) $values[$count / 2]) / 2;
        }

        return (float) $values[(int) floor($count / 2)];
    }
}
