<?php

namespace App\Services\Workflow;

use App\Enums\IssueStatus;
use App\Events\IssueStatusChanged;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Single entry point for every issue status change.
 *
 * Guarantees:
 *  - transitions are validated against {@see IssueTransitionRules}
 *  - the status update + history record happen atomically under a row lock
 *  - real-time broadcast is dispatched only after the transaction commits
 */
final class WorkflowService
{
    public function __construct(
        private readonly IssueTransitionRules $rules,
    ) {}

    /**
     * Perform a status transition atomically and record its history.
     *
     * @return array{
     *     issue: Issue,
     *     from_status: string,
     *     to_status: string,
     *     history_id: int,
     *     actor_id: int|null,
     *     actor_name: string|null
     * }
     *
     * @throws ValidationException
     */
    public function transition(
        int $issueId,
        string $newStatus,
        ?User $actor = null,
        ?string $reason = null,
    ): array {
        $result = DB::transaction(function () use ($issueId, $newStatus, $actor, $reason) {
            /** @var Issue $issue */
            $issue = Issue::query()
                ->lockForUpdate()
                ->findOrFail($issueId);

            $oldStatus = $issue->status->value;

            if ($oldStatus === $newStatus) {
                throw ValidationException::withMessages([
                    'status' => 'The issue is already in this status.',
                ]);
            }

            if (! $this->rules->canTransition($oldStatus, $newStatus)) {
                throw ValidationException::withMessages([
                    'status' => "Transition from '{$oldStatus}' to '{$newStatus}' is not allowed.",
                ]);
            }

            // Business rule: an issue cannot become "assigned" with no assignee.
            if ($newStatus === IssueStatus::ASSIGNED->value && ! $issue->assigned_to) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot set status to "Assigned" without an assignee.',
                ]);
            }

            $issue->status = $newStatus;

            $this->applyLifecycleTimestamps($issue, $newStatus);

            $issue->save();

            $history = $issue->statusHistories()->create([
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'changed_by' => $actor?->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            return [
                'issue' => $issue,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'history_id' => $history->id,
                'actor_id' => $actor?->id,
                'actor_name' => $actor?->name,
            ];
        });

        // Broadcast only after a successful commit so listeners never observe
        // a status that was rolled back.
        DB::afterCommit(function () use ($result) {
            event(new IssueStatusChanged(
                $result['issue'],
                $result['from_status'],
                $result['to_status'],
                $result['history_id'],
                $result['actor_name'],
                (string) Str::uuid(),
                $result['actor_id'],
            ));
        });

        return [
            'issue' => $result['issue']->fresh(['project', 'reporter', 'assignee']),
            'from_status' => $result['from_status'],
            'to_status' => $result['to_status'],
            'history_id' => $result['history_id'],
            'actor_id' => $result['actor_id'],
            'actor_name' => $result['actor_name'],
        ];
    }

    /**
     * Record the initial status when an issue is created.
     * Call inside the same transaction that creates the issue.
     */
    public function recordInitialStatus(Issue $issue, ?User $actor = null): void
    {
        $issue->statusHistories()->create([
            'from_status' => null,
            'to_status' => $issue->status->value,
            'changed_by' => $actor?->id,
            'reason' => 'Issue created',
            'created_at' => now(),
        ]);
    }

    /**
     * Keep resolved_at / closed_at / completed_at in sync with the status.
     */
    private function applyLifecycleTimestamps(Issue $issue, string $newStatus): void
    {
        if ($newStatus === IssueStatus::RESOLVED->value) {
            $issue->resolved_at = now();
            $issue->completed_at = now();
        } elseif ($newStatus === IssueStatus::CLOSED->value) {
            $issue->closed_at = now();
            $issue->completed_at = now();
        } elseif ($newStatus === IssueStatus::REOPENED->value) {
            $issue->resolved_at = null;
            $issue->closed_at = null;
            $issue->completed_at = null;
        }
    }
}
