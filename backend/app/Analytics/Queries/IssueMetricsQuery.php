<?php

namespace App\Analytics\Queries;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Support\MetricDefinitions;
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
            ->where('project_id', $filters->projectId)
            ->when($filters->priorities !== [], fn (Builder $q) => $q->whereIn('priority', $filters->priorities))
            ->when($filters->statuses !== [], fn (Builder $q) => $q->whereIn('status', $filters->statuses))
            ->when($filters->assigneeId, fn (Builder $q) => $q->where('assigned_to', $filters->assigneeId))
            ->when($filters->sprintId, fn (Builder $q) => $q->where('sprint_id', $filters->sprintId));
    }

    /**
     * KPI summary for a project.
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
                ->whereBetween('created_at', [$filters->periodStart, $filters->periodEnd])
                ->count(),
            'resolved_in_period' => (clone $base)
                ->whereIn('status', MetricDefinitions::allResolvedStatuses())
                ->whereRaw('COALESCE(resolved_at, updated_at) BETWEEN ? AND ?', [
                    $filters->periodStart->toDateTimeString(),
                    $filters->periodEnd->toDateTimeString(),
                ])
                ->count(),
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
        return DB::table('issues')
            ->where('project_id', $filters->projectId)
            ->when($filters->statuses !== [], fn ($q) => $q->whereIn('status', $filters->statuses))
            ->when($filters->priorities !== [], fn ($q) => $q->whereIn('priority', $filters->priorities))
            ->when($filters->assigneeId, fn ($q) => $q->where('assigned_to', $filters->assigneeId))
            ->when($filters->sprintId, fn ($q) => $q->where('sprint_id', $filters->sprintId))
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'status' => (string) $row->status,
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
        return DB::table('issues')
            ->where('project_id', $filters->projectId)
            ->when($filters->statuses !== [], fn ($q) => $q->whereIn('status', $filters->statuses))
            ->when($filters->priorities !== [], fn ($q) => $q->whereIn('priority', $filters->priorities))
            ->when($filters->assigneeId, fn ($q) => $q->where('assigned_to', $filters->assigneeId))
            ->when($filters->sprintId, fn ($q) => $q->where('sprint_id', $filters->sprintId))
            ->select('priority', DB::raw('COUNT(*) as total'))
            ->groupBy('priority')
            ->get()
            ->map(fn ($row) => [
                'priority' => (string) $row->priority,
                'count' => (int) $row->total,
            ])
            ->all();
    }
}
