<?php

namespace App\Analytics\Queries;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Support\MetricDefinitions;
use App\Enums\IssueStatus;
use App\Enums\Priority;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IssueMetricsQuery
{
    /**
     * Base query honouring the optional filters on the DTO.
     */
    protected function base(AnalyticsFilters $filters): Builder
    {
        return Issue::query()
            ->when($filters->projectIds !== [], fn (Builder $q) => $q->whereIn('project_id', $filters->projectIds))
            ->when($filters->priorities !== [], fn (Builder $q) => $q->whereIn('priority', $filters->priorities))
            ->when($filters->statuses !== [], fn (Builder $q) => $q->whereIn('status', $filters->statuses))
            ->when($filters->assigneeId, fn (Builder $q) => $q->where('assigned_to', $filters->assigneeId))
            ->when($filters->sprintId, fn (Builder $q) => $q->where('sprint_id', $filters->sprintId));
    }

    /**
     * Half-open period window: [periodStart, periodEnd).
     *
     * @return array{0: string, 1: string}
     */
    protected function window(AnalyticsFilters $filters): array
    {
        return [$filters->periodStart->toDateTimeString(), $filters->periodEnd->toDateTimeString()];
    }

    /**
     * KPI summary for the scoped projects.
     *
     * All six counters are computed in a single pass over the scoped rows
     * (conditional SUMs) instead of issuing six separate COUNT queries.
     *
     * @return array<string, int>
     */
    public function summary(AnalyticsFilters $filters): array
    {
        [$start, $end] = $this->window($filters);

        $openStatuses = MetricDefinitions::allOpenStatuses();
        $resolvedStatuses = MetricDefinitions::allResolvedStatuses();
        $openIn = $this->placeholders($openStatuses);
        $resolvedIn = $this->placeholders($resolvedStatuses);

        $row = $this->base($filters)
            ->selectRaw('COUNT(*) as total_issues')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status IN ({$openIn}) THEN 1 ELSE 0 END), 0) as open_issues",
                $openStatuses,
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END), 0) as created_in_period',
                [$start, $end],
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status IN ({$resolvedIn}) AND resolved_at IS NOT NULL AND resolved_at >= ? AND resolved_at < ? THEN 1 ELSE 0 END), 0) as resolved_in_period",
                array_merge($resolvedStatuses, [$start, $end]),
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status IN ({$openIn}) AND due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END), 0) as overdue_issues",
                array_merge($openStatuses, [now()->toDateTimeString()]),
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status IN ({$openIn}) AND priority = ? THEN 1 ELSE 0 END), 0) as critical_open_issues",
                array_merge($openStatuses, ['critical']),
            )
            ->first();

        return [
            'total_issues' => (int) $row->total_issues,
            'open_issues' => (int) $row->open_issues,
            'created_in_period' => (int) $row->created_in_period,
            'resolved_in_period' => (int) $row->resolved_in_period,
            'overdue_issues' => (int) $row->overdue_issues,
            'critical_open_issues' => (int) $row->critical_open_issues,
        ];
    }

    /**
     * Build a positional placeholder list for an IN clause.
     *
     * @param  list<string>  $values
     */
    protected function placeholders(array $values): string
    {
        return implode(',', array_fill(0, count($values), '?'));
    }

    /**
     * Distribution by status.
     *
     * @return list<array{status: string, count: int}>
     */
    public function byStatus(AnalyticsFilters $filters): array
    {
        return $this->base($filters)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status instanceof IssueStatus
                    ? $row->status->value
                    : (string) $row->status,
                'count' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * Distribution by priority.
     *
     * @return list<array{priority: string, count: int}>
     */
    public function byPriority(AnalyticsFilters $filters): array
    {
        return $this->base($filters)
            ->select('priority', DB::raw('COUNT(*) as total'))
            ->groupBy('priority')
            ->get()
            ->map(fn ($row) => [
                'priority' => $row->priority instanceof Priority
                    ? $row->priority->value
                    : (string) $row->priority,
                'count' => (int) $row->total,
            ])
            ->all();
    }
}
