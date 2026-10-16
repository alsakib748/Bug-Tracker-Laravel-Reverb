<?php

namespace App\Services;

use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Enums\Priority;
use App\Enums\Severity;
use App\Events\IssueAssigned;
use App\Events\IssueCreated;
use App\Events\IssueStatusChanged;
use App\Events\IssueUpdated;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;

class IssueService
{

    protected NotificationService $notificationService;
    protected ActivityLogService $activityLogService;

    public function __construct(NotificationService $notificationService, ActivityLogService $activityLogService)
    {
        $this->notificationService = $notificationService;
        $this->activityLogService = $activityLogService;
    }

    public function getAllIssues(array $filters = [], int $perPage = 15, ?User $user = null): LengthAwarePaginator
    {
        $user ??= auth()->user();

        $query = Issue::query()
            ->when($user, fn($q) => $q->visibleTo($user))
            ->with(['project', 'reporter', 'assignee', 'comments', 'attachments'])
            ->when($filters['project_id'] ?? null, fn($q, $v) => $q->where('project_id', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['priority'] ?? null, fn($q, $v) => $q->where('priority', $v))
            ->when($filters['severity'] ?? null, fn($q, $v) => $q->where('severity', $v))
            ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($filters['assigned_to'] ?? null, fn($q, $v) => $q->where('assigned_to', $v))
            ->when($filters['reporter_id'] ?? null, fn($q, $v) => $q->where('reporter_id', $v))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($filters['sort'] ?? null, function ($q, $sort) {
                $direction = 'asc';
                if (str_starts_with($sort, '-')) {
                    $direction = 'desc';
                    $sort = substr($sort, 1);
                }
                $q->orderBy($sort, $direction);
            }, fn($q) => $q->orderBy('id', 'desc'));

        return $query->paginate($perPage);

    }

    public function getIssueById(int $id, ?User $user = null): Issue
    {
        $user ??= auth()->user();

        $issue = Issue::with(['project', 'reporter', 'assignee', 'comments.user', 'attachments'])
            ->findOrFail($id);

        if ($user && !$issue->isVisibleTo($user)) {
            abort(403, 'You are not allowed to view this issue.');
        }

        return $issue;
    }

    public function createIssue(array $data): Issue
    {

        $project = Project::findOrFail($data['project_id']);
        $reporter = User::findOrFail($data['reporter_id']);

        $issue = Issue::create([
            'project_id' => $data['project_id'],
            'reporter_id' => $data['reporter_id'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? Priority::MEDIUM->value,
            'severity' => $data['severity'] ?? Severity::MAJOR->value,
            'status' => IssueStatus::OPEN->value,
            'type' => $data['type'] ?? IssueType::BUG->value,
            'due_date' => $data['due_date'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
        ]);

        event(new IssueCreated($issue));

        $this->notificationService->sendIssueCreated($issue, $reporter);
        $this->activityLogService->logIssueCreated($issue, auth()->user());
        // Refresh with relations

        return $issue->fresh(['project', 'reporter', 'assignee']);

    }

    public function updateIssue(Issue $issue, array $data): Issue
    {

        $old = $issue->getOriginal();

        $issue->update([
            'title' => $data['title'] ?? $issue->title,
            'description' => $data['description'] ?? $issue->description,
            'priority' => $data['priority'] ?? $issue->priority,
            'severity' => $data['severity'] ?? $issue->severity,
            'type' => $data['type'] ?? $issue->type,
            'due_date' => $data['due_date'] ?? $issue->due_date,
            'estimated_hours' => $data['estimated_hours'] ?? $issue->estimated_hours,
        ]);

        event(new IssueUpdated($issue->fresh()));

        $this->activityLogService->logIssueUpdated($issue, auth()->user(), $old, $issue->getAttributes());

        return $issue->fresh(['project', 'reporter', 'assignee']);

    }

    public function deleteIssue(Issue $issue): void
    {
        $issue->delete();
    }

    // -------------------- Business Actions -------------------
    /**
     * Assign (or reassign) a developer to an issue.
     * If issue is open, transition to assigned.
     */
    public function assignIssue(Issue $issue, int $userId): Issue
    {
        $user = User::findOrFail($userId);

        if (!$issue->project->members()->where('user_id', $userId)->exists()) {
            throw new \Exception('User is not a member of this project.');
        }

        $issue->assigned_to = $userId;

        // If status is 'open', change to 'assigned'
        if ($issue->status === IssueStatus::OPEN) {
            $issue->status = IssueStatus::ASSIGNED;
        }

        $issue->save();

        $issue->refresh();

        event(new IssueAssigned(
            $issue->load(['project', 'reporter', 'assignee']),
            $user->id
        ));

        event(new IssueUpdated(
            $issue->load(['project', 'reporter', 'assignee'])
        ));

        $this->activityLogService->logIssueAssigned($issue, auth()->user(), $user);

        $this->notificationService->sendIssueAssigned(
            $issue,
            $user,
            auth()->user()
        );

        return $issue->fresh(['project', 'reporter', 'assignee']);

    }

    /**
     * Change status of an issue following the workflow.
     * Allowed transitions:
     *   - open → assigned (only if assigned_to is set)
     *   - assigned → in_progress
     *   - in_progress → code_review
     *   - code_review → testing
     *   - testing → resolved
     *   - resolved → closed
     *   - testing → reopened
     *   - reopened → in_progress
     *
     * Also allow any status from any? For now, we enforce the workflow.
     */
    public function changeStatus(Issue $issue, string $newStatus): Issue
    {
        $current = $issue->status;
        $oldStatus = $current->value;
        $new = IssueStatus::tryFrom($newStatus);

        if (!$new) {
            throw new \Exception('Invalid status value.');
        }

        // Define allowed transitions
        $transitions = [
            IssueStatus::OPEN->value => [IssueStatus::ASSIGNED->value],
            IssueStatus::ASSIGNED->value => [IssueStatus::IN_PROGRESS->value],
            IssueStatus::IN_PROGRESS->value => [IssueStatus::CODE_REVIEW->value],
            IssueStatus::CODE_REVIEW->value => [IssueStatus::TESTING->value],
            IssueStatus::TESTING->value => [IssueStatus::RESOLVED->value, IssueStatus::REOPENED->value],
            IssueStatus::RESOLVED->value => [IssueStatus::CLOSED->value, IssueStatus::REOPENED->value],
            IssueStatus::REOPENED->value => [IssueStatus::IN_PROGRESS->value],
            IssueStatus::CLOSED->value => [], // closed is final
        ];

        if (!in_array($newStatus, $transitions[$current->value] ?? [])) {
            throw new \Exception("Cannot transition from {$current->label()} to {$new->label()}.");
        }

        // Additional validation:
        // - Cannot set status to 'assigned' without an assignee.
        if ($new === IssueStatus::ASSIGNED && !$issue->assigned_to) {
            throw new \Exception('Cannot set status to "Assigned" without an assignee.');
        }

        $issue->status = $new;

        // If closing, set completed_at; if reopening, clear it
        if ($new === IssueStatus::CLOSED) {
            $issue->completed_at = \Illuminate\Support\Carbon::now();
        } elseif ($new === IssueStatus::REOPENED) {
            $issue->completed_at = null;
        }

        $issue->save();

        event(new IssueStatusChanged($issue->fresh(), $oldStatus, $new->value));

        $this->notificationService->sendIssueStatusChanged($issue, $oldStatus, $new->value, auth()->user());

        $this->activityLogService->logIssueStatusChanged($issue, auth()->user(), $oldStatus, $new->value);

        return $issue->fresh(['project', 'reporter', 'assignee']);

    }

    /**
     * Reopen a resolved issue.
     * This is a specific action: sets status to 'reopened' and keeps assignee.
     */

    public function reopenIssue(Issue $issue): Issue
    {
        if ($issue->status !== IssueStatus::RESOLVED) {
            throw new \Exception('Only resolved issues can be reopened.');
        }

        $oldStatus = $issue->status->value;

        $issue->status = IssueStatus::REOPENED;
        $issue->completed_at = null;
        $issue->save();

        $issue = $issue->fresh(['project', 'reporter', 'assignee']);

        event(new IssueStatusChanged($issue, $oldStatus, IssueStatus::REOPENED->value));

        $this->notificationService->sendIssueStatusChanged($issue, $oldStatus, IssueStatus::REOPENED->value, auth()->user());

        $this->activityLogService->logIssueReopened($issue, auth()->user());

        return $issue;
    }

    /**
     * Close a resolved issue.
     */
    public function closeIssue(Issue $issue): Issue
    {
        if ($issue->status !== IssueStatus::RESOLVED) {
            throw new \Exception('Only resolved issues can be closed.');
        }

        $oldStatus = $issue->status->value;

        $issue->status = IssueStatus::CLOSED;
        $issue->completed_at = \Illuminate\Support\Carbon::now();
        $issue->save();

        $issue = $issue->fresh(['project', 'reporter', 'assignee']);

        event(new IssueStatusChanged($issue, $oldStatus, IssueStatus::CLOSED->value));

        $this->notificationService->sendIssueStatusChanged($issue, $oldStatus, IssueStatus::CLOSED->value, auth()->user());

        $this->activityLogService->logIssueClosed($issue, auth()->user());

        return $issue;
    }

}
