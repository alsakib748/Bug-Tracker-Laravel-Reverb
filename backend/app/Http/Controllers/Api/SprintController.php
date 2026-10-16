<?php

namespace App\Http\Controllers\Api;

use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddIssuesToSprintRequest;
use App\Http\Requests\CompleteSprintRequest;
use App\Http\Requests\StoreSprintRequest;
use App\Http\Requests\UpdateSprintRequest;
use App\Http\Resources\BoardIssueResource;
use App\Http\Resources\SprintResource;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Services\Sprints\SprintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SprintController extends Controller
{
    public function __construct(
        private readonly SprintService $sprints,
    ) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        return SprintResource::collection(
            $project->sprints()
                ->withCount('issues')
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function store(StoreSprintRequest $request, Project $project): SprintResource
    {
        $this->authorize('update', $project);

        $sprint = $this->sprints->createSprint(
            $project,
            $request->validated(),
            $request->user(),
        );

        return new SprintResource($sprint);
    }

    public function show(Sprint $sprint): SprintResource
    {
        $this->authorize('view', $sprint->project);

        $sprint->load([
            'issues' => fn ($query) => $query
                ->with(['assignee:id,name,avatar', 'labels:id,name,color'])
                ->orderBy('board_position'),
        ]);

        return new SprintResource($sprint);
    }

    public function update(UpdateSprintRequest $request, Sprint $sprint): SprintResource
    {
        $this->authorize('update', $sprint->project);

        return new SprintResource(
            $this->sprints->updateSprint($sprint, $request->validated())
        );
    }

    public function addIssues(AddIssuesToSprintRequest $request, Sprint $sprint): Response
    {
        $this->authorize('update', $sprint->project);

        $this->sprints->addIssues(
            $sprint,
            $request->validated('issue_ids'),
            $request->user(),
        );

        return response()->noContent();
    }

    public function removeIssue(Sprint $sprint, Issue $issue): Response
    {
        $this->authorize('update', $sprint->project);

        $this->sprints->removeIssue($sprint, $issue, request()->user());

        return response()->noContent();
    }

    public function start(Sprint $sprint): SprintResource
    {
        $this->authorize('update', $sprint->project);

        return new SprintResource(
            $this->sprints->start($sprint, request()->user())
        );
    }

    public function complete(CompleteSprintRequest $request, Sprint $sprint): SprintResource
    {
        $this->authorize('update', $sprint->project);

        return new SprintResource(
            $this->sprints->complete(
                $sprint,
                $request->validated('carry_over'),
                $request->validated('target_sprint_id'),
                $request->user(),
            )
        );
    }

    public function cancel(Sprint $sprint): SprintResource
    {
        $this->authorize('update', $sprint->project);

        return new SprintResource(
            $this->sprints->cancel($sprint, request()->user())
        );
    }

    public function report(Sprint $sprint): JsonResponse
    {
        $this->authorize('view', $sprint->project);

        $issues = $sprint->issues()
            ->with(['assignee:id,name,avatar', 'labels:id,name,color'])
            ->orderBy('board_position')
            ->get();

        $statusCounts = [];

        foreach (IssueStatus::cases() as $status) {
            $count = $issues
                ->filter(fn (Issue $issue) => $issue->status->value === $status->value)
                ->count();

            if ($count > 0) {
                $statusCounts[] = [
                    'status' => $status->value,
                    'label' => $status->label(),
                    'count' => $count,
                ];
            }
        }

        $done = [IssueStatus::RESOLVED->value, IssueStatus::CLOSED->value];

        return response()->json([
            'data' => [
                'sprint' => new SprintResource($sprint),
                'issues' => BoardIssueResource::collection($issues),
                'status_counts' => $statusCounts,
                'total_issues' => $issues->count(),
                'total_story_points' => (int) $issues->sum('story_points'),
                'completed_story_points' => (int) $issues
                    ->filter(fn (Issue $issue) => in_array($issue->status->value, $done, true))
                    ->sum('story_points'),
            ],
        ]);
    }
}
