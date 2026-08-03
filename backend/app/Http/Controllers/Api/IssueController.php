<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignIssueRequest;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Services\IssueService;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    protected IssueService $issueService;

    public function __construct(IssueService $issueService)
    {
        $this->issueService = $issueService;
    }

    /**
     * List issues with filters.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Issue::class);

        $filters = $request->only([
            'project_id',
            'status',
            'priority',
            'severity',
            'type',
            'assigned_to',
            'reporter_id',
            'search',
            'sort',
        ]);

        $perPage = $request->get('per_page', 15);

        $issues = $this->issueService->getAllIssues($filters, $perPage);

        return IssueResource::collection($issues);

    }

    /**
     * Create a new issue.
     */
    public function store(StoreIssueRequest $request)
    {
        // $this->authorize('viewAny', Issue::class);

        $data = $request->validated();

        $data['reporter_id'] = $request->user()->id;

        $issue = $this->issueService->createIssue($data);

        return new IssueResource($issue);

    }

    /**
     * Show a single issue (full detail).
     */
    public function show(Issue $issue)
    {
        // $this->authorize('view', $issue);

        $issue->load(['project', 'reporter', 'assignee', 'comments.user', 'attachments']);
        return new IssueResource($issue);
    }

    /**
     * Update issue details (excluding status and assignment).
     */
    public function update(UpdateIssueRequest $request, Issue $issue)
    {
        // $this->authorize('update', $issue);

        $updated = $this->issueService->updateIssue($issue, $request->validated());

        return new IssueResource($updated);
    }

    /**
     * Delete an issue.
     */
    public function destroy(Issue $issue)
    {
        // $this->authorize('delete', $issue);

        $this->issueService->deleteIssue($issue);

        return response()->json(['message' => 'Issue deleted successfully.']);
    }

    // ---------- Business Actions ----------

    /**
     * Assign a developer.
     */
    public function assign(AssignIssueRequest $request, Issue $issue)
    {
        // $this->authorize('assign', $issue);

        $issue = $this->issueService->assignIssue($issue, $request->user_id);

        return new IssueResource($issue);
    }

    /**
     * Change status (workflow).
     */
    public function changeStatus(ChangeStatusRequest $request, Issue $issue)
    {
        // $this->authorize('changeStatus', $issue);

        $issue = $this->issueService->changeStatus($issue, $request->status);

        return new IssueResource($issue);
    }

    /**
     * Reopen a resolved issue.
     */
    public function reopen(Issue $issue)
    {
        // $this->authorize('reopen', $issue);

        $issue = $this->issueService->reopenIssue($issue);

        return new IssueResource($issue);
    }

    /**
     * Close a resolved issue.
     */
    public function close(Issue $issue)
    {
        // $this->authorize('close', $issue);

        $issue = $this->issueService->closeIssue($issue);

        return new IssueResource($issue);
    }

}
