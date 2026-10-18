<?php

namespace App\Analytics\Queries;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Support\MetricDefinitions;
use App\Models\Issue;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ResolutionMetricsQuery
{
    /**
     * Median resolution time in hours for issues resolved in the period.
     *
     * Prefers resolved_at; falls back to updated_at when resolved_at is not
     * set, and reports the resulting coverage so callers can judge accuracy.
     *
     * @return array{median_hours: float|null, sample_size: int, coverage: string}
     */
    public function medianResolutionHours(AnalyticsFilters $filters): array
    {
        $issues = Issue::query()
            ->where('project_id', $filters->projectId)
            ->whereIn('status', MetricDefinitions::allResolvedStatuses())
            ->where(function ($query) use ($filters) {
                $query
                    ->whereBetween('resolved_at', [$filters->periodStart, $filters->periodEnd])
                    ->orWhere(function ($fallback) use ($filters) {
                        $fallback->whereNull('resolved_at')
                            ->whereBetween('updated_at', [$filters->periodStart, $filters->periodEnd]);
                    });
            })
            ->get(['created_at', 'resolved_at', 'updated_at']);

        if ($issues->isEmpty()) {
            return [
                'median_hours' => null,
                'sample_size' => 0,
                'coverage' => 'unknown',
            ];
        }

        $estimated = $issues->contains(fn (Issue $issue) => $issue->resolved_at === null);

        $durations = $issues
            ->map(function (Issue $issue) {
                $end = $issue->resolved_at ?? $issue->updated_at;

                if (! $end || ! $issue->created_at) {
                    return null;
                }

                return abs((float) $issue->created_at->diffInMinutes($end)) / 60;
            })
            ->filter(fn (?float $hours) => $hours !== null && $hours >= 0)
            ->sort()
            ->values();

        if ($durations->isEmpty()) {
            return [
                'median_hours' => null,
                'sample_size' => 0,
                'coverage' => 'unknown',
            ];
        }

        return [
            'median_hours' => round($this->median($durations), 1),
            'sample_size' => $durations->count(),
            'coverage' => $estimated ? 'estimated' : 'complete',
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
        $created = Issue::query()
            ->where('project_id', $filters->projectId)
            ->whereBetween('created_at', [$filters->periodStart, $filters->periodEnd])
            ->pluck('created_at')
            ->map(fn ($value) => $this->bucket((string) $value, $granularity))
            ->countBy();

        $resolved = Issue::query()
            ->where('project_id', $filters->projectId)
            ->whereIn('status', MetricDefinitions::allResolvedStatuses())
            ->where(function ($query) use ($filters) {
                $query
                    ->whereBetween('resolved_at', [$filters->periodStart, $filters->periodEnd])
                    ->orWhere(function ($fallback) use ($filters) {
                        $fallback->whereNull('resolved_at')
                            ->whereBetween('updated_at', [$filters->periodStart, $filters->periodEnd]);
                    });
            })
            ->get(['resolved_at', 'updated_at'])
            ->map(function (Issue $issue) use ($granularity) {
                $value = $issue->resolved_at ?? $issue->updated_at;

                return $value ? $this->bucket($value->toDateTimeString(), $granularity) : null;
            })
            ->filter()
            ->countBy();

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

    protected function bucket(string $value, string $granularity): string
    {
        $date = Carbon::parse($value);

        return match ($granularity) {
            'week' => $date->isoFormat('GGGG-[W]WW'),
            'month' => $date->format('Y-m'),
            default => $date->format('Y-m-d'),
        };
    }
}
