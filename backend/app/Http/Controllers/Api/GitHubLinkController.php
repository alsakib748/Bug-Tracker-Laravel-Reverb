<?php

namespace App\Http\Controllers\Api;

use App\Enums\GitHubEntityType;
use App\Enums\GitHubLinkSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\GitHub\StoreGitHubLinkRequest;
use App\Http\Resources\GitHubIssueLinkResource;
use App\Http\Resources\GitHubPullRequestResource;
use App\Integrations\GitHub\Services\GitHubLinkingService;
use App\Models\GitHubCheckRun;
use App\Models\GitHubIssueLink;
use App\Models\GitHubPullRequest;
use App\Models\GitHubRepository;
use App\Models\Issue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GitHubLinkController extends Controller
{
    public function __construct(
        private readonly GitHubLinkingService $service,
    ) {
    }

    public function index(Issue $issue): AnonymousResourceCollection
    {
        $this->authorize('view', $issue);

        $links = $issue->githubLinks()->with('repository')->orderByDesc('created_at')->get();

        $this->attachPullRequestState($links);

        return GitHubIssueLinkResource::collection($links);
    }

    /**
     * Attach the current PR state and latest CI check to pull-request links so
     * the issue page can show status badges without extra requests.
     */
    private function attachPullRequestState($links): void
    {
        $prLinks = $links->where('entity_type', GitHubEntityType::PULL_REQUEST->value);

        if ($prLinks->isEmpty()) {
            return;
        }

        $pullRequests = $prLinks->groupBy('github_repository_id')
            ->map(fn ($repoLinks, $repositoryId) => GitHubPullRequest::where('github_repository_id', $repositoryId)
                ->whereIn('github_pr_id', $repoLinks->pluck('github_entity_id')->unique()->all())
                ->get())
            ->flatten(1);

        $prByKey = $pullRequests->keyBy(
            fn ($pr) => $pr->github_repository_id . ':' . $pr->github_pr_id
        );

        $shas = $pullRequests->pluck('head_sha')->filter()->unique()->all();
        $checkByKey = [];

        if ($shas !== []) {
            GitHubCheckRun::whereIn('head_sha', $shas)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get()
                ->each(function (GitHubCheckRun $check) use (&$checkByKey) {
                    // First occurrence per sha wins (ordered by most recent).
                    $checkByKey[$check->head_sha] ??= $check;
                });
        }

        foreach ($prLinks as $link) {
            $pr = $prByKey->get($link->github_repository_id . ':' . $link->github_entity_id);

            if ($pr) {
                $link->setRelation('pullRequest', $pr);

                if ($pr->head_sha && isset($checkByKey[$pr->head_sha])) {
                    $link->setRelation('latestCheckRun', $checkByKey[$pr->head_sha]);
                }
            }
        }
    }

    /**
     * Open PRs from the project's linked repositories that are not yet
     * linked to this issue — the "candidates" for manual linking.
     */
    public function candidates(Issue $issue): AnonymousResourceCollection
    {
        $this->authorize('view', $issue);

        $repositoryIds = $issue->project->githubRepositories()->pluck('github_repositories.id');

        $linkedPrIds = $issue->githubLinks()
            ->where('entity_type', GitHubEntityType::PULL_REQUEST->value)
            ->pluck('github_entity_id');

        $pullRequests = GitHubPullRequest::whereIn('github_repository_id', $repositoryIds)
            ->whereNotIn('github_pr_id', $linkedPrIds)
            ->where('state', 'open')
            ->with('repository')
            ->orderByDesc('opened_at')
            ->limit(50)
            ->get();

        return GitHubPullRequestResource::collection($pullRequests);
    }

    /**
     * Manually link a PR to the issue.
     */
    public function store(StoreGitHubLinkRequest $request, Issue $issue): GitHubIssueLinkResource
    {
        $this->authorize('update', $issue);

        $pr = GitHubPullRequest::with('repository')->findOrFail(
            (int) $request->validated('github_pull_request_id')
        );

        $link = $this->service->linkPullRequest(
            $issue,
            $pr,
            $request->user()->id,
            GitHubLinkSource::MANUAL,
        );

        return new GitHubIssueLinkResource($link->load('repository'));
    }

    public function destroy(Request $request, GitHubIssueLink $link): Response
    {
        $this->authorize('update', $link->issue);

        $this->service->unlink($link);

        return response()->noContent();
    }
}
