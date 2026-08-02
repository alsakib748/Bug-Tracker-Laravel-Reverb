<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectMemberRequest;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectMemberService;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    protected ProjectMemberService $memberService;

    public function __construct(ProjectMemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    /**
     * * List all members of a project.
     */
    public function index(Project $project)
    {
        // $this->authorize('viewMembers', $project);

        $members = $this->memberService->getMembers($project);

        return UserResource::collection($members);

    }

    /**
     * * Add a member to the project.
     */
    public function store(StoreProjectMemberRequest $request, Project $project)
    {
        // $this->authorize('manageMembers', $project);
        // dd($request);
        try {
            $this->memberService->addMember(
                $project,
                $request->user_id,
                $request->role
            );

            return response()->json(['message' => 'Member added successfully'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * * Remove a member from the project.
     */
    public function destroy(Project $project, User $user)
    {
        // $this->authorize('manageMembers', $project);

        try {
            $this->memberService->removeMember($project, $user->id);
            return response()->json(['message' => 'Member removed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message', $e->getMessage()], 422);
        }

    }

    /**
     * * Get users available to be added to the project.
     */
    public function available(Project $project)
    {
        // $this->authorize('manageMembers', $project);

        $users = $this->memberService->getAvailableUsers($project);
        return UserResource::collection($users);
    }

}
