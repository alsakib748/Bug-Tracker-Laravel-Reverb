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

    public function getAllIssues(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Issue::query()
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

    public function getIssueById(int $id): Issue
    {
        return Issue::with(['project', 'reporter', 'assignee', 'comments.user', 'attachments'])
            ->findOrFail($id);
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
            'due_date' => $data['due_data'] ?? null,
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

        // If status if 'open', change to 'assigned'
        if ($issue->status === IssueStatus::OPEN->value) {
            $issue->status = IssueStatus::ASSIGNED;
        }

        // Send notification
        $assignee = User::find($userId);
        $notificationService = app(NotificationService::class);
        $notificationService->sendIssueAssigned($issue, $assignee, auth()->user());

        $issue->save();

        event(new IssueAssigned($issue));
        event(new IssueUpdated($issue));

        $this->activityLogService->logIssueAssigned($issue, auth()->user(), $assignee);

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
        $oldStatus = $issue->status;
        $current = IssueStatus::tryFrom($issue->status);
        $new = IssueStatus::tryFrom($newStatus);

        if (!$current || !$new) {
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
        // - Cannot assign to 'assigned' if no assignee? We'll allow, but assignIssue handles that.
        if ($newStatus === IssueStatus::ASSIGNED->value && !$issue->assigned_to) {
            throw new \Exception('Cannot set status to "Assigned" without an assignee.');
        }

        $issue->status = $new;

        // If closing, set completed_at
        if ($new === IssueStatus::CLOSED) {
            $issue->completed_at = \Illuminate\Support\Carbon::now();
        }

        $issue->save();


        event(new IssueStatusChanged($issue, $oldStatus, $newStatus));

        $this->notificationService->sendIssueStatusChanged($issue, $current->value, $new->value, auth()->user());

        $this->activityLogService->logIssueStatusChanged($issue, auth()->user(), $oldStatus, $newStatus);

        return $issue->fresh(['project', 'reporter', 'assignee']);

    }

    /**
     * Reopen a resolved issue.
     * This is a specific action: sets status to 'reopened' and keeps assignee.
     */

    public function reopenIssue(Issue $issue): Issue
    {
        if ($issue->status !== IssueStatus::RESOLVED->value) {
            throw new \Exception('Only resolved issues can be reopened.');
        }

        $issue->status = IssueStatus::REOPENED;
        $issue->save();

        $this->activityLogService->logIssueReopened($issue, auth()->user());

        // TODO: Broadcast event, log activity

        return $issue->fresh(['project', 'reporter', 'assignee']);
    }

    /**
     * Close a resolved issue.
     */
    public function closeIssue(Issue $issue): Issue
    {
        if ($issue->status !== IssueStatus::RESOLVED->value) {
            throw new \Exception('Only resolved issues can be closed.');
        }

        $issue->status = IssueStatus::CLOSED;
        $issue->completed_at = \Illuminate\Support\Carbon::now();
        $issue->save();

        // TODO: Broadcast event, log activity

        return $issue->fresh(['project', 'reporter', 'assignee']);
    }

}
