<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectInsightResource;
use App\Jobs\GenerateProjectInsights;
use App\Models\Project;
use App\Models\ProjectInsight;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectInsightsController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $insights = ProjectInsight::query()
            ->where('project_id', $project->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return ProjectInsightResource::collection($insights);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $validated = $request->validate([
            'insight_type' => 'required|in:project_summary,trend_analysis,bottleneck,sprint_review',
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
            'timezone' => 'nullable|timezone',
        ]);

        $start = ! empty($validated['start'])
            ? Carbon::parse($validated['start'])
            : now()->subDays(30);

        $end = ! empty($validated['end'])
            ? Carbon::parse($validated['end'])
            : now();

        $insight = ProjectInsight::create([
            'project_id' => $project->id,
            'requested_by' => $request->user()->id,
            'insight_type' => $validated['insight_type'],
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'status' => 'queued',
            'filters' => [
                'timezone' => $validated['timezone'] ?? config('app.timezone', 'UTC'),
            ],
            'prompt_version' => 'v1',
        ]);

        GenerateProjectInsights::dispatch($insight->id);

        return (new ProjectInsightResource($insight))
            ->response()
            ->setStatusCode(202);
    }

    public function show(ProjectInsight $insight): ProjectInsightResource
    {
        $this->authorize('view', $insight->project);

        return new ProjectInsightResource($insight);
    }
}
