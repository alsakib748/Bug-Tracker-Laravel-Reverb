<?php

namespace App\Http\Controllers\Api;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Services\ProjectAnalyticsService;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectAnalyticsController extends Controller
{
    public function __construct(
        private readonly ProjectAnalyticsService $analytics,
    ) {}

    public function overview(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $filters = AnalyticsFilters::fromRequest($request, $project->id);

        return response()->json($this->analytics->overview($filters));
    }

    public function trends(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $filters = AnalyticsFilters::fromRequest($request, $project->id);

        return response()->json(
            $this->analytics->trends($filters, (string) $request->input('granularity', 'day'))
        );
    }

    public function workflow(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $filters = AnalyticsFilters::fromRequest($request, $project->id);

        return response()->json($this->analytics->workflow($filters));
    }

    public function github(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $filters = AnalyticsFilters::fromRequest($request, $project->id);

        return response()->json($this->analytics->github($filters));
    }
}
