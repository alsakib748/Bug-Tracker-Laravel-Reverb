<?php

namespace App\Analytics\Queries;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Support\MetricDefinitions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Development-velocity metrics derived from the stored GitHub facts
 * (pull requests, commits and their issue links). These are pure counts and
 * durations — interpretation happens in the AI insight layer, never here.
 *
 * Durations and week buckets are computed in PHP (rather than via MySQL-only
 * functions such as TIMESTAMPDIFF/DATE_FORMAT) so the metrics behave
 * identically on MySQL and SQLite.
 */
class GitHubAnalyticsQuery
{
    /**
     * Hours between an issue's creation and the first linked PR being opened.
     *
     * @return array{median_hours: float|null, sample_size: int, coverage: string}
     */
    public function timeToFirstPR(AnalyticsFilters $filters): array
    {
        $durations = DB::table('issues')
            ->join('github_issue_links', 'github_issue_links.issue_id', '=', 'issues.id')
            ->join('github_pull_requests', function ($join) {
                $join->on('github_pull_requests.github_repository_id', '=', 'github_issue_links.github_repository_id')
                    ->on('github_pull_requests.github_pr_id', '=', 'github_issue_links.github_entity_id');
            })
            ->where('issues.project_id', $filters->projectId)
            ->where('github_issue_links.entity_type', 'pull_request')
            ->whereNotNull('github_pull_requests.opened_at')
            ->whereBetween('issues.created_at', [$filters->periodStart, $filters->periodEnd])
            ->select('issues.created_at', 'github_pull_requests.opened_at')
            ->get()
            ->map(fn ($row) => $this->hoursBetween($row->created_at, $row->opened_at))
            ->filter(fn ($hours) => $hours !== null && $hours >= 0)
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

        $count = DB::table('github_pull_requests')
            ->join('github_project_repositories', 'github_project_repositories.github_repository_id', '=', 'github_pull_requests.github_repository_id')
            ->where('github_project_repositories.project_id', $filters->projectId)
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
        $opened = $this->weeklyBuckets(
            DB::table('github_pull_requests')
                ->join('github_project_repositories', 'github_project_repositories.github_repository_id', '=', 'github_pull_requests.github_repository_id')
                ->where('github_project_repositories.project_id', $filters->projectId)
                ->whereBetween('github_pull_requests.opened_at', [$filters->periodStart, $filters->periodEnd])
                ->pluck('github_pull_requests.opened_at'),
        );

        $merged = $this->weeklyBuckets(
            DB::table('github_pull_requests')
                ->join('github_project_repositories', 'github_project_repositories.github_repository_id', '=', 'github_pull_requests.github_repository_id')
                ->where('github_project_repositories.project_id', $filters->projectId)
                ->whereNotNull('github_pull_requests.merged_at')
                ->whereBetween('github_pull_requests.merged_at', [$filters->periodStart, $filters->periodEnd])
                ->pluck('github_pull_requests.merged_at'),
        );

        $buckets = collect(array_keys($opened))
            ->merge(array_keys($merged))
            ->unique()
            ->sort()
            ->values();

        return [
            'buckets' => $buckets->all(),
            'opened' => $buckets->map(fn ($bucket) => (int) ($opened[$bucket] ?? 0))->all(),
            'merged' => $buckets->map(fn ($bucket) => (int) ($merged[$bucket] ?? 0))->all(),
        ];
    }

    /**
     * Median pull request duration (opened -> merged) in hours.
     *
     * @return array{median_hours: float|null, sample_size: int}
     */
    public function prMergeDuration(AnalyticsFilters $filters): array
    {
        $durations = DB::table('github_pull_requests')
            ->join('github_project_repositories', 'github_project_repositories.github_repository_id', '=', 'github_pull_requests.github_repository_id')
            ->where('github_project_repositories.project_id', $filters->projectId)
            ->whereNotNull('github_pull_requests.merged_at')
            ->whereNotNull('github_pull_requests.opened_at')
            ->whereBetween('github_pull_requests.merged_at', [$filters->periodStart, $filters->periodEnd])
            ->select('github_pull_requests.opened_at', 'github_pull_requests.merged_at')
            ->get()
            ->map(fn ($row) => $this->hoursBetween($row->opened_at, $row->merged_at))
            ->filter(fn ($hours) => $hours !== null && $hours >= 0)
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
        return DB::table('issues')
            ->leftJoin('github_issue_links', function ($join) {
                $join->on('github_issue_links.issue_id', '=', 'issues.id')
                    ->where('github_issue_links.entity_type', 'pull_request');
            })
            ->where('issues.project_id', $filters->projectId)
            ->whereIn('issues.status', MetricDefinitions::allOpenStatuses())
            ->whereNull('github_issue_links.id')
            ->count();
    }

    /**
     * Signed hours between two timestamps (to - from).
     */
    protected function hoursBetween(mixed $from, mixed $to): ?float
    {
        if ($from === null || $to === null) {
            return null;
        }

        return (Carbon::parse($to)->timestamp - Carbon::parse($from)->timestamp) / 3600;
    }

    /**
     * Bucket a list of timestamps into ISO week labels ("o-\WW", e.g. 2026-W42).
     *
     * @param  Collection<int, mixed>  $timestamps
     * @return array<string, int>
     */
    protected function weeklyBuckets(Collection $timestamps): array
    {
        $counts = [];

        foreach ($timestamps as $timestamp) {
            if ($timestamp === null) {
                continue;
            }

            $bucket = Carbon::parse($timestamp)->format('o-\WW');
            $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;
        }

        ksort($counts);

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
