<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{

    protected ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        try {
            // $this->authorize('viewAny', Project::class);

            // $filters = $request->only(['search', 'status']);
            // $perpage = $request->get('per_page', 15);

            // $projects = $this->projectService->getAllProjects($filters, $perPage);

            $projects = $this->projectService->getAllProjects();

            return ProjectResource::collection($projects);
        } catch (\Exception $e) {
            \Log::error('Project index error: ' . $e->getMessage());
            return resonse()->json(['error' => $e->getMessage()], 500);
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        // $this->authorize('create', Project::class);

        $project = $this->projectService->createProject($request->validated());

        return new ProjectResource($project);

    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        // $this->authorize('view', $project);
        $project = $this->projectService->getProject($project);

        return new ProjectResource($project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        // $this->authorize('update', $project);

        $project = $this->projectService->updateProject($project, $request->validated());

        return new ProjectResource($project);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        // $this->authorize('delete', $project);

        try {
            $this->projectService->deleteProject($project);
            return response()->json([
                'status' => 'success',
                'message' => 'Project deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

    }
}