<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MoveBoardIssueRequest;
use App\Http\Resources\BoardIssueResource;
use App\Models\Issue;
use App\Models\Project;
use App\Services\Board\BoardPositionService;
use App\Services\Board\BoardQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function __construct(
        private readonly BoardQueryService $board,
        private readonly BoardPositionService $positions,
    ) {}

    /**
     * Board columns for a project, optionally limited to a single sprint.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $sprintId = $request->filled('sprint_id') ? $request->integer('sprint_id') : null;

        $columns = array_map(fn (array $column) => [
            'status' => $column['status'],
            'label' => $column['label'],
            'color' => $column['color'],
            'issues' => BoardIssueResource::collection($column['issues']),
        ], $this->board->getBoard($project, $sprintId));

        return response()->json(['data' => ['columns' => $columns]]);
    }

    /**
     * Move an issue between columns / positions.
     */
    public function move(MoveBoardIssueRequest $request, Issue $issue): BoardIssueResource
    {
        $this->authorize('update', $issue);

        $updated = $this->positions->move(
            $issue,
            $request->validated('status'),
            (int) $request->validated('position'),
            $request->user(),
        );

        return new BoardIssueResource($updated);
    }
}
