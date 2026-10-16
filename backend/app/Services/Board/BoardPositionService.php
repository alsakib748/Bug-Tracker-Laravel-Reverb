<?php

namespace App\Services\Board;

use App\Events\BoardIssueMoved;
use App\Models\Issue;
use App\Models\User;
use App\Services\Workflow\WorkflowService;
use Illuminate\Support\Facades\DB;

/**
 * Persists Kanban board / backlog ordering.
 *
 * Moving an issue across columns delegates the status change to the
 * {@see WorkflowService} (so transition rules + history are preserved), while
 * same-column moves only update the ordering position.
 */
final class BoardPositionService
{
    public function __construct(
        private readonly WorkflowService $workflow,
    ) {}

    /**
     * Move an issue to a target status column at the given position.
     */
    public function move(Issue $issue, string $status, int $position, User $actor): Issue
    {
        $result = DB::transaction(function () use ($issue, $status, $position, $actor) {
            /** @var Issue $locked */
            $locked = Issue::query()->lockForUpdate()->findOrFail($issue->id);

            $oldStatus = $locked->status->value;

            // WorkflowService rejects no-op transitions, so only delegate when
            // the column actually changes. Same-column drags are pure reorders.
            if ($oldStatus !== $status) {
                $this->workflow->transition($locked->id, $status, $actor);
            }

            $locked->board_position = $position;
            $locked->version = (int) $locked->version + 1;
            $locked->save();

            return [
                'issue' => $locked,
                'from_status' => $oldStatus,
                'to_status' => $status,
            ];
        });

        DB::afterCommit(function () use ($result, $position, $actor) {
            event(new BoardIssueMoved(
                $result['issue'],
                $result['from_status'],
                $result['to_status'],
                $position,
                $actor->name,
            ));
        });

        return $result['issue']->fresh(['assignee', 'labels', 'sprint']);
    }

    /**
     * Persist a new position for an issue while it sits in the backlog.
     */
    public function reorderBacklog(Issue $issue, int $position): Issue
    {
        DB::transaction(function () use ($issue, $position) {
            /** @var Issue $locked */
            $locked = Issue::query()->lockForUpdate()->findOrFail($issue->id);
            $locked->backlog_position = $position;
            $locked->save();
        });

        return $issue->fresh();
    }
}
