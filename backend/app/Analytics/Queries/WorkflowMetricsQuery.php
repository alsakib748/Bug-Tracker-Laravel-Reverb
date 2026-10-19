<?php

namespace App\Analytics\Queries;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Models\IssueStatusHistory;
use Illuminate\Support\Collection;

class WorkflowMetricsQuery
{
    /**
     * Median time in each status based on status history transitions.
     *
     * @return list<array{status: string, median_hours: float, sample_size: int}>
     */
    public function timeInStatus(AnalyticsFilters $filters): array
    {
        $transitions = IssueStatusHistory::query()
            ->whereHas('issue', fn ($query) => $query->when(
                $filters->projectIds !== [],
                fn ($q) => $q->whereIn('project_id', $filters->projectIds)
            ))
            ->where('created_at', '>=', $filters->periodStart)
            ->where('created_at', '<', $filters->periodEnd)
            ->orderBy('issue_id')
            ->orderBy('created_at')
            ->get(['issue_id', 'to_status', 'created_at']);

        $durationsByStatus = [];

        foreach ($transitions->groupBy('issue_id') as $issueTransitions) {
            $previousAt = null;
            $previousStatus = null;

            foreach ($issueTransitions as $transition) {
                if ($previousAt && $previousStatus) {
                    $durationsByStatus[$previousStatus][] = abs(
                        (float) $previousAt->diffInMinutes($transition->created_at)
                    ) / 60;
                }

                $previousAt = $transition->created_at;
                $previousStatus = $transition->to_status;
            }
        }

        $result = [];

        foreach ($durationsByStatus as $status => $durations) {
            $values = collect($durations)->sort()->values();

            $result[] = [
                'status' => (string) $status,
                'median_hours' => round($this->median($values), 1),
                'sample_size' => $values->count(),
            ];
        }

        usort($result, fn ($a, $b) => $b['sample_size'] <=> $a['sample_size']);

        return $result;
    }

    /**
     * Number of distinct issues reopened in the period.
     */
    public function reopenedCount(AnalyticsFilters $filters): int
    {
        return IssueStatusHistory::query()
            ->whereHas('issue', fn ($query) => $query->when(
                $filters->projectIds !== [],
                fn ($q) => $q->whereIn('project_id', $filters->projectIds)
            ))
            ->where('to_status', 'reopened')
            ->where('created_at', '>=', $filters->periodStart)
            ->where('created_at', '<', $filters->periodEnd)
            ->distinct()
            ->count('issue_id');
    }

    /**
     * @param  Collection<int, float>  $values  already sorted
     */
    protected function median(Collection $values): float
    {
        $count = $values->count();

        if ($count === 0) {
            return 0.0;
        }

        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }
}
