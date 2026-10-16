<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BoardIssueResource;
use App\Models\Issue;
use App\Models\Project;
use App\Services\Board\BoardPositionService;
use App\Services\Board\BoardQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BacklogController extends Controller
{
    public function __construct(
        private readonly BoardQueryService $board,
        private readonly BoardPositionService $positions,
    ) {}

    /**
     * Backlog issues (no sprint, still actionable) ordered by position.
     */
    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        return BoardIssueResource::collection($this->board->getBacklog($project));
    }

    /**
     * Persist a new backlog ordering position for an issue.
     */
    public function reorder(Request $request, Issue $issue): BoardIssueResource
    {
        $this->authorize('update', $issue);

        $validated = $request->validate([
            'position' => ['required', 'integer', 'min:0'],
        ]);

        return new BoardIssueResource(
            $this->positions->reorderBacklog($issue, (int) $validated['position'])
        );
    }
}
