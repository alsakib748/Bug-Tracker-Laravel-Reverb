<?php

namespace App\Analytics\Queries;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Support\MetricDefinitions;
use App\Analytics\Support\SqlDialect;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
     * Durations are computed in SQL (driver-aware) so only small numeric
     * values cross the database boundary; only the median itself stays in PHP.
     *
     * @return array{median_hours: float|null, sample_size: int, legacy_sample_size: int, coverage: string}
     */
    public function medianResolutionHours(AnalyticsFilters $filters): array
    {
        $modern = $this->durations(
            $filters,
            fn ($query) => $query
                ->whereNotNull('resolved_at')
                ->where('resolved_at', '>=', $filters->periodStart)
                ->where('resolved_at', '<', $filters->periodEnd),
            'resolved_at',
        );

        $legacy = $this->durations(
            $filters,
            fn ($query) => $query
                ->whereNull('resolved_at')
                ->where('updated_at', '>=', $filters->periodStart)
                ->where('updated_at', '<', $filters->periodEnd),
            'updated_at',
        );

        $all = $modern->concat($legacy)->sort()->values();

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
            'legacy_sample_size' => $legacy->count(),
            'coverage' => $legacy->isEmpty() ? 'complete' : 'estimated',
        ];
    }

    /**
     * Created vs resolved series for a period. Buckets are aggregated per day
     * in SQL, then folded into the requested granularity in PHP so week/month
     * labels match the API contract on both MySQL and SQLite.
     *
     * @return array{buckets: list<string>, created: list<int>, resolved: list<int>}
     */
    public function createdVsResolved(AnalyticsFilters $filters, string $granularity = 'day'): array
    {
        $created = $this->dailyCounts(
            $filters,
            'created_at',
            fn ($query) => $query
                ->where('created_at', '>=', $filters->periodStart)
                ->where('created_at', '<', $filters->periodEnd),
        );

        $resolvedModern = $this->dailyCounts(
            $filters,
            'resolved_at',
            fn ($query) => $query
                ->whereIn('status', MetricDefinitions::allResolvedStatuses())
                ->whereNotNull('resolved_at')
                ->where('resolved_at', '>=', $filters->periodStart)
                ->where('resolved_at', '<', $filters->periodEnd),
        );

        $resolvedLegacy = $this->dailyCounts(
            $filters,
            'updated_at',
            fn ($query) => $query
                ->whereIn('status', MetricDefinitions::allResolvedStatuses())
                ->whereNull('resolved_at')
                ->where('updated_at', '>=', $filters->periodStart)
                ->where('updated_at', '<', $filters->periodEnd),
        );

        $createdBuckets = SqlDialect::bucketDays($created, $granularity);

        $resolvedBuckets = SqlDialect::bucketDays($resolvedModern, $granularity);
        foreach (SqlDialect::bucketDays($resolvedLegacy, $granularity) as $label => $count) {
            $resolvedBuckets[$label] = ($resolvedBuckets[$label] ?? 0) + $count;
        }
        ksort($resolvedBuckets);

        $buckets = collect(array_keys($createdBuckets))
            ->merge(array_keys($resolvedBuckets))
            ->unique()
            ->sort()
            ->values();

        return [
            'buckets' => $buckets->all(),
            'created' => $buckets->map(fn ($bucket) => (int) ($createdBuckets[$bucket] ?? 0))->all(),
            'resolved' => $buckets->map(fn ($bucket) => (int) ($resolvedBuckets[$bucket] ?? 0))->all(),
        ];
    }

    /**
     * Resolution durations (hours) for issues resolved in the scoped period.
     *
     * @param  callable(\Illuminate\Database\Query\Builder): \Illuminate\Database\Query\Builder  $constrain
     * @return Collection<int, float>
     */
    protected function durations(AnalyticsFilters $filters, callable $constrain, string $endColumn): Collection
    {
        $query = DB::table('issues')
            ->when($filters->projectIds !== [], fn ($q) => $q->whereIn('project_id', $filters->projectIds))
            ->whereIn('status', MetricDefinitions::allResolvedStatuses());

        $query = $constrain($query);

        return $query
            ->selectRaw(SqlDialect::minutesBetween('created_at', $endColumn).' as minutes')
            ->pluck('minutes')
            ->filter(fn ($minutes) => $minutes !== null)
            ->map(fn ($minutes) => (float) $minutes / 60)
            ->values();
    }

    /**
     * Daily counts keyed by "Y-m-d".
     *
     * @param  callable(\Illuminate\Database\Query\Builder): \Illuminate\Database\Query\Builder  $constrain
     * @return array<string, int>
     */
    protected function dailyCounts(AnalyticsFilters $filters, string $dateColumn, callable $constrain): array
    {
        $query = DB::table('issues')
            ->when($filters->projectIds !== [], fn ($q) => $q->whereIn('project_id', $filters->projectIds));

        $query = $constrain($query);

        $counts = [];

        foreach ($query
            ->selectRaw(SqlDialect::date($dateColumn).' as bucket, COUNT(*) as total')
            ->groupBy('bucket')
            ->get() as $row) {
            $counts[(string) $row->bucket] = (int) $row->total;
        }

        return $counts;
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
}
