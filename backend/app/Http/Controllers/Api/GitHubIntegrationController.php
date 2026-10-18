<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GitHub\ConnectGitHubRequest;
use App\Http\Requests\GitHub\LinkGitHubRepositoryRequest;
use App\Http\Resources\GitHubInstallationResource;
use App\Http\Resources\GitHubRepositoryResource;
use App\Integrations\GitHub\Jobs\SyncRepositoryPullRequests;
use App\Integrations\GitHub\Services\GitHubInstallationService;
use App\Models\GitHubInstallation;
use App\Models\GitHubRepository;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GitHubIntegrationController extends Controller
{
    public function __construct(private readonly GitHubInstallationService $service) {}

    /**
     * Verify an installation (after the GitHub App install redirect) and
     * sync its repositories.
     */
    public function connect(ConnectGitHubRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageGitHub', $project);

        try {
            $installation = $this->service->verifyInstallation(
                (int) $request->validated('installation_id'),
                $request->validated('account'),
            );
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => 'GitHub connected.',
            'installation' => new GitHubInstallationResource($installation->load('repositories')),
        ]);
    }

    /**
     * All active repositories visible to any installation (linked ones flagged).
     */
    public function repositories(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $linkedIds = $project->githubRepositories()->pluck('github_repositories.id');

        $repositories = GitHubRepository::where('status', 'active')
            ->orderBy('full_name')
            ->get();

        $data = collect(GitHubRepositoryResource::collection($repositories)->toArray($request))
            ->map(function (array $resource) use ($linkedIds) {
                $resource['linked'] = $linkedIds->contains($resource['id']);

                return $resource;
            })
            ->values();

        return response()->json([
            'repositories' => $data,
        ]);
    }

    /**
     * Link a repository to the project.
     */
    public function linkRepository(LinkGitHubRepositoryRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageGitHub', $project);

        $repositoryId = (int) $request->validated('github_repository_id');

        $project->githubRepositories()->syncWithoutDetaching([
            $repositoryId => [
                'linked_by' => $request->user()->id,
                'is_default' => $request->boolean('is_default', false),
            ],
        ]);

        // Backfill existing open pull requests so they can be linked right away.
        SyncRepositoryPullRequests::dispatch($repositoryId)->afterCommit();

        return response()->json(['message' => 'Repository linked.']);
    }

    /**
     * Re-fetch a linked repository's open pull requests from GitHub.
     */
    public function syncRepository(Request $request, Project $project, GitHubRepository $repository): JsonResponse
    {
        $this->authorize('manageGitHub', $project);

        if (! $project->githubRepositories()->whereKey($repository->id)->exists()) {
            return response()->json(['message' => 'Repository is not linked to this project.'], 422);
        }

        SyncRepositoryPullRequests::dispatch($repository->id)->afterCommit();

        return response()->json(['message' => 'Pull request sync queued.']);
    }

    /**
     * Unlink a repository from the project.
     */
    public function unlinkRepository(Request $request, Project $project, GitHubRepository $repository): JsonResponse
    {
        $this->authorize('manageGitHub', $project);

        $project->githubRepositories()->detach($repository->id);

        return response()->json(['message' => 'Repository unlinked.']);
    }

    /**
     * Current installation(s) state for the integration page.
     */
    public function status(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $installation = GitHubInstallation::where('status', 'active')
            ->latest('last_verified_at')
            ->first();

        return response()->json([
            'installation' => $installation
                ? new GitHubInstallationResource($installation->load('repositories'))
                : null,
            'linked_repositories' => GitHubRepositoryResource::collection(
                $project->githubRepositories()->orderBy('full_name')->get()
            ),
        ]);
    }
}
