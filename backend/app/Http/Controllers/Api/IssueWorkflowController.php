<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransitionIssueRequest;
use App\Http\Resources\IssueResource;
use App\Http\Resources\IssueStatusHistoryResource;
use App\Models\Issue;
use App\Services\Workflow\IssueTransitionRules;
use App\Services\Workflow\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueWorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowService $workflow,
        private readonly IssueTransitionRules $rules,
    ) {
    }

    /**
     * Perform a status transition and record it in the history.
     */
    public function transition(TransitionIssueRequest $request, Issue $issue): IssueResource
    {
        $this->authorize('changeStatus', $issue);

        $result = $this->workflow->transition(
            issueId: $issue->id,
            newStatus: $request->validated('to_status'),
            actor: $request->user(),
            reason: $request->validated('reason'),
        );

        return new IssueResource($result['issue']);
    }

    /**
     * Paginated status history for an issue.
     */
    public function history(Request $request, Issue $issue): JsonResponse
    {
        $this->authorize('view', $issue);

        $perPage = (int) $request->get('per_page', 20);

        $history = $issue->statusHistories()
            ->with('actor:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => IssueStatusHistoryResource::collection($history->items()),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        ]);
    }

    /**
     * Allowed next statuses for the current state of the issue.
     */
    public function allowedTransitions(Issue $issue): JsonResponse
    {
        $this->authorize('view', $issue);

        return response()->json([
            'current' => $issue->status->value,
            'allowed' => $this->rules->allowedNextStatuses($issue->status->value),
        ]);
    }
}
