<?php

namespace App\Services\Board;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Read-only queries backing the Kanban board and the product backlog.
 */
final class BoardQueryService
{
    /**
     * Statuses rendered as board columns, in workflow order.
     *
     * @return array<int, IssueStatus>
     */
    private function boardStatuses(): array
    {
        return [
            IssueStatus::OPEN,
            IssueStatus::ASSIGNED,
            IssueStatus::IN_PROGRESS,
            IssueStatus::CODE_REVIEW,
            IssueStatus::TESTING,
            IssueStatus::RESOLVED,
            IssueStatus::CLOSED,
        ];
    }

    /**
     * Board columns with their issues. Optionally scoped to a single sprint.
     *
     * @return array<int, array{status: string, label: string, color: string, issues: Collection<int, Issue>}>
     */
    public function getBoard(Project $project, ?int $sprintId = null): array
    {
        $query = Issue::query()
            ->where('project_id', $project->id)
            ->with(['assignee:id,name,avatar', 'labels:id,name,color']);

        if ($sprintId !== null) {
            $query->where('sprint_id', $sprintId);
        }

        $issues = $query
            ->orderBy('board_position')
            ->orderBy('id')
            ->get();

        $columns = [];

        foreach ($this->boardStatuses() as $status) {
            $columns[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                // Compare enum instances by value: Eloquent casts status to an
                // enum, so a raw string comparison would never match.
                'issues' => $issues
                    ->filter(fn (Issue $issue) => $issue->status->value === $status->value)
                    ->values(),
            ];
        }

        return $columns;
    }

    /**
     * Issues that are not assigned to any sprint and are still actionable.
     *
     * @return Collection<int, Issue>
     */
    public function getBacklog(Project $project): Collection
    {
        return Issue::query()
            ->where('project_id', $project->id)
            ->whereNull('sprint_id')
            ->whereNotIn('status', [
                IssueStatus::RESOLVED->value,
                IssueStatus::CLOSED->value,
            ])
            ->with(['assignee:id,name,avatar', 'labels:id,name,color'])
            ->withCount(['comments', 'attachments'])
            ->orderBy('backlog_position')
            ->orderByDesc('id')
            ->get();
    }
}
