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
     * Issues resolved within the period, keyed on the authoritative resolved_at
     * timestamp (set by WorkflowService on the resolved transition).
     */
    protected function resolvedInPeriod(Builder $query, AnalyticsFilters $filters): Builder
    {
        return $query
            ->whereIn('status', MetricDefinitions::allResolvedStatuses())
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '>=', $filters->periodStart)
            ->where('resolved_at', '<', $filters->periodEnd);
    }

    /**
     * KPI summary for the scoped projects.
     *
     * @return array<string, int>
     */
    public function summary(AnalyticsFilters $filters): array
    {
        $base = $this->base($filters);

        return [
            'total_issues' => (clone $base)->count(),
            'open_issues' => (clone $base)
                ->whereIn('status', MetricDefinitions::allOpenStatuses())
                ->count(),
            'created_in_period' => (clone $base)
                ->where('created_at', '>=', $filters->periodStart)
                ->where('created_at', '<', $filters->periodEnd)
                ->count(),
            'resolved_in_period' => $this->resolvedInPeriod(clone $base, $filters)->count(),
            'overdue_issues' => (clone $base)
                ->whereIn('status', MetricDefinitions::allOpenStatuses())
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count(),
            'critical_open_issues' => (clone $base)
                ->whereIn('status', MetricDefinitions::allOpenStatuses())
                ->where('priority', 'critical')
                ->count(),
        ];
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
