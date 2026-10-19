<?php

namespace App\Analytics\Queries;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Support\MetricDefinitions;
use App\Models\Issue;
use Illuminate\Support\Collection;

class ResolutionMetricsQuery
{
    /**
     * Median resolution time in hours for issues resolved in the period.
     *
     * Resolution is measured with the authoritative resolved_at timestamp set
     * by WorkflowService. Issues resolved before resolved_at existed are
     * reported separately via the legacy_sample_size/coverage keys so callers
     * can judge accuracy.
     *
     * @return array{median_hours: float|null, sample_size: int, legacy_sample_size: int, coverage: string}
     */
    public function medianResolutionHours(AnalyticsFilters $filters): array
    {
        $scoped = fn () => Issue::query()
            ->when($filters->projectIds !== [], fn ($q) => $q->whereIn('project_id', $filters->projectIds))
            ->whereIn('status', MetricDefinitions::allResolvedStatuses());

        $modern = $scoped()
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $filters->periodStart)
            ->where('resolved_at', '<', $filters->periodEnd)
            ->get(['created_at', 'resolved_at']);

        $legacy = $scoped()
            ->whereNull('resolved_at')
            ->where('updated_at', '>=', $filters->periodStart)
            ->where('updated_at', '<', $filters->periodEnd)
            ->get(['created_at', 'updated_at']);

        $modernDurations = $this->durations($modern->pluck('resolved_at'), $modern->pluck('created_at'));
        $legacyDurations = $this->durations($legacy->pluck('updated_at'), $legacy->pluck('created_at'));

        $all = $modernDurations->concat($legacyDurations)->sort()->values();

        if ($all->isEmpty()) {
            return [
                'median_hours' => null,
                'sample_size' => 0,
                'legacy_sample_size' => 0,
                'coverage' => 'unknown',
            ];
        }

        return [
            'median_hours' => round($this->median($all), 1),
            'sample_size' => $all->count(),
            'legacy_sample_size' => $legacyDurations->count(),
            'coverage' => $legacyDurations->isEmpty() ? 'complete' : 'estimated',
        ];
    }

    /**
     * Created vs resolved series for a period, bucketed in PHP so the metric
     * works identically on MySQL and SQLite.
     *
     * @return array{buckets: list<string>, created: list<int>, resolved: list<int>}
     */
    public function createdVsResolved(AnalyticsFilters $filters, string $granularity = 'day'): array
    {
        $scope = fn ($query) => $filters->projectIds !== []
            ? $query->whereIn('project_id', $filters->projectIds)
            : $query;

        $created = $scope(Issue::query())
            ->where('created_at', '>=', $filters->periodStart)
            ->where('created_at', '<', $filters->periodEnd)
            ->get(['created_at'])
            ->countBy(fn (Issue $issue) => $this->bucket($issue->created_at, $granularity));

        $resolvedModern = $scope(Issue::query())
            ->whereIn('status', MetricDefinitions::allResolvedStatuses())
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $filters->periodStart)
            ->where('resolved_at', '<', $filters->periodEnd)
            ->get(['resolved_at'])
            ->countBy(fn (Issue $issue) => $this->bucket($issue->resolved_at, $granularity));

        $resolvedLegacy = $scope(Issue::query())
            ->whereIn('status', MetricDefinitions::allResolvedStatuses())
            ->whereNull('resolved_at')
            ->where('updated_at', '>=', $filters->periodStart)
            ->where('updated_at', '<', $filters->periodEnd)
            ->get(['updated_at'])
            ->countBy(fn (Issue $issue) => $this->bucket($issue->updated_at, $granularity));

        $resolved = $resolvedModern->merge($resolvedLegacy);

        $buckets = $created->keys()
            ->merge($resolved->keys())
            ->unique()
            ->sort()
            ->values();

        return [
            'buckets' => $buckets->all(),
            'created' => $buckets->map(fn ($bucket) => (int) ($created[$bucket] ?? 0))->all(),
            'resolved' => $buckets->map(fn ($bucket) => (int) ($resolved[$bucket] ?? 0))->all(),
        ];
    }

    /**
     * @param  Collection<int, \Carbon\Carbon|null>  $ends
     * @param  Collection<int, \Carbon\Carbon|null>  $starts
     * @return Collection<int, float>
     */
    protected function durations(Collection $ends, Collection $starts): Collection
    {
        return $ends
            ->zip($starts)
            ->map(function ($pair) {
                [$end, $start] = $pair;

                if (! $end || ! $start) {
                    return null;
                }

                return abs((float) $start->diffInMinutes($end)) / 60;
            })
            ->filter(fn ($hours) => $hours !== null && $hours >= 0)
            ->values();
    }

    /**
     * @param  Collection<int, float>  $values  already sorted
     */
    protected function median(Collection $values): float
    {
        $count = $values->count();
        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }

    protected function bucket($value, string $granularity): string
    {
        return match ($granularity) {
            'week' => $value->isoFormat('GGGG-[W]WW'),
            'month' => $value->format('Y-m'),
            default => $value->format('Y-m-d'),
        };
    }
}
