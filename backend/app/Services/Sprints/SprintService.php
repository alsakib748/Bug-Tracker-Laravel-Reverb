<?php

namespace App\Services\Sprints;

use App\Enums\SprintStatus;
use App\Models\Issue;
use App\Models\IssueSprintHistory;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sprint lifecycle: plan -> start -> complete/cancel.
 *
 * Every mutation that touches issue assignment is wrapped in a transaction and
 * records an {@see IssueSprintHistory} entry so the movement of work between
 * sprints is fully auditable.
 */
final class SprintService
{
    public function createSprint(Project $project, array $data, User $actor): Sprint
    {
        return Sprint::create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'goal' => $data['goal'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => SprintStatus::PLANNED->value,
            'created_by' => $actor->id,
        ]);
    }

    public function updateSprint(Sprint $sprint, array $data): Sprint
    {
        if (! $sprint->status->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => 'Only planned sprints can be edited.',
            ]);
        }

        $sprint->update(array_intersect_key($data, array_flip([
            'name', 'goal', 'start_date', 'end_date',
        ])));

        return $sprint->fresh();
    }

    /**
     * @param  array<int, int|string>  $issueIds
     */
    public function addIssues(Sprint $sprint, array $issueIds, User $actor): void
    {
        if (! $sprint->status->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => 'Only planned sprints can accept new issues.',
            ]);
        }

        $issueIds = array_values(array_unique(array_map('intval', $issueIds)));

        DB::transaction(function () use ($sprint, $issueIds, $actor) {
            $issues = Issue::query()
                ->whereIn('id', $issueIds)
                ->where('project_id', $sprint->project_id)
                ->lockForUpdate()
                ->get();

            if ($issues->count() !== count($issueIds)) {
                throw ValidationException::withMessages([
                    'issue_ids' => 'One or more issues do not belong to this project.',
                ]);
            }

            $conflict = $issues->first(fn (Issue $issue) => $issue->sprint_id && $issue->sprint_id !== $sprint->id);

            if ($conflict) {
                throw ValidationException::withMessages([
                    'issue_ids' => "Issue #{$conflict->id} is already assigned to another sprint.",
                ]);
            }

            foreach ($issues as $issue) {
                $fromSprintId = $issue->sprint_id;

                $issue->sprint_id = $sprint->id;
                $issue->save();

                $this->recordHistory($issue, $fromSprintId, $sprint->id, $actor, 'Added to sprint');
            }
        });
    }

    public function removeIssue(Sprint $sprint, Issue $issue, User $actor): void
    {
        if (! $sprint->status->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => 'Only planned sprints can remove issues.',
            ]);
        }

        if ($issue->sprint_id !== $sprint->id) {
            throw ValidationException::withMessages([
                'issue' => 'Issue is not part of this sprint.',
            ]);
        }

        DB::transaction(function () use ($issue, $actor) {
            $fromSprintId = $issue->sprint_id;
            $issue->sprint_id = null;
            $issue->save();

            $this->recordHistory($issue, $fromSprintId, null, $actor, 'Removed from sprint');
        });
    }

    public function start(Sprint $sprint, User $actor): Sprint
    {
        return DB::transaction(function () use ($sprint) {
            // Serialize concurrent "start sprint" calls for the same project.
            Project::query()->where('id', $sprint->project_id)->lockForUpdate()->first();

            $sprint = Sprint::query()->lockForUpdate()->findOrFail($sprint->id);

            if (! $sprint->status->canBeStarted()) {
                throw ValidationException::withMessages([
                    'status' => 'Only planned sprints can be started.',
                ]);
            }

            if (! $sprint->start_date || ! $sprint->end_date) {
                throw ValidationException::withMessages([
                    'dates' => 'Start date and end date are required to start a sprint.',
                ]);
            }

            $activeExists = Sprint::query()
                ->where('project_id', $sprint->project_id)
                ->where('status', SprintStatus::ACTIVE->value)
                ->where('id', '!=', $sprint->id)
                ->exists();

            if ($activeExists) {
                throw ValidationException::withMessages([
                    'status' => 'Another sprint is already active for this project.',
                ]);
            }

            $sprint->status = SprintStatus::ACTIVE->value;
            $sprint->started_at = now();
            $sprint->save();

            return $sprint->fresh();
        });
    }

    public function complete(Sprint $sprint, string $carryOverMode, ?int $targetSprintId, User $actor): Sprint
    {
        return DB::transaction(function () use ($sprint, $carryOverMode, $targetSprintId, $actor) {
            $sprint = Sprint::query()->lockForUpdate()->findOrFail($sprint->id);

            if (! $sprint->status->canBeCompleted()) {
                throw ValidationException::withMessages([
                    'status' => 'Only active sprints can be completed.',
                ]);
            }

            $targetSprint = null;

            if ($carryOverMode === 'move_to_sprint') {
                $targetSprint = Sprint::query()
                    ->where('project_id', $sprint->project_id)
                    ->where('status', SprintStatus::PLANNED->value)
                    ->where('id', '!=', $sprint->id)
                    ->find($targetSprintId);

                if (! $targetSprint) {
                    throw ValidationException::withMessages([
                        'target_sprint_id' => 'A valid planned sprint in this project is required.',
                    ]);
                }
            }

            $unfinished = Issue::query()
                ->where('sprint_id', $sprint->id)
                ->whereNotIn('status', ['resolved', 'closed'])
                ->lockForUpdate()
                ->get();

            foreach ($unfinished as $issue) {
                $fromSprintId = $issue->sprint_id;

                $issue->sprint_id = $carryOverMode === 'backlog' ? null : $targetSprint->id;
                $issue->save();

                $this->recordHistory(
                    $issue,
                    $fromSprintId,
                    $issue->sprint_id,
                    $actor,
                    $carryOverMode === 'backlog'
                        ? 'Carried over to backlog'
                        : "Carried over to sprint #{$targetSprint->id}",
                );
            }

            $sprint->status = SprintStatus::COMPLETED->value;
            $sprint->completed_at = now();
            $sprint->save();

            return $sprint->fresh();
        });
    }

    public function cancel(Sprint $sprint, User $actor): Sprint
    {
        return DB::transaction(function () use ($sprint, $actor) {
            $sprint = Sprint::query()->lockForUpdate()->findOrFail($sprint->id);

            if ($sprint->status === SprintStatus::COMPLETED) {
                throw ValidationException::withMessages([
                    'status' => 'Completed sprints cannot be cancelled.',
                ]);
            }

            $issues = Issue::query()->where('sprint_id', $sprint->id)->lockForUpdate()->get();

            foreach ($issues as $issue) {
                $fromSprintId = $issue->sprint_id;
                $issue->sprint_id = null;
                $issue->save();

                $this->recordHistory($issue, $fromSprintId, null, $actor, 'Sprint cancelled');
            }

            $sprint->status = SprintStatus::CANCELLED->value;
            $sprint->save();

            return $sprint->fresh();
        });
    }

    private function recordHistory(
        Issue $issue,
        ?int $fromSprintId,
        ?int $toSprintId,
        User $actor,
        string $reason,
    ): void {
        IssueSprintHistory::create([
            'issue_id' => $issue->id,
            'from_sprint_id' => $fromSprintId,
            'to_sprint_id' => $toSprintId,
            'changed_by' => $actor->id,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
