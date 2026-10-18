<?php

namespace App\Integrations\GitHub\Services;

use App\Enums\GitHubLinkSource;
use App\Integrations\GitHub\Clients\GitHubApiClient;
use App\Integrations\GitHub\Events\PullRequestSynced;
use App\Models\GitHubPullRequest;
use App\Models\GitHubRepository;
use Illuminate\Support\Carbon;

class GitHubPullRequestSyncService
{
    public function __construct(
        private readonly GitHubApiClient $client,
        private readonly GitHubLinkingService $linking,
    ) {}

    /**
     * Upsert a PR from GitHub payload data. Shared by webhook handling and
     * API backfill so both paths map fields identically.
     */
    public function upsertPullRequest(
        GitHubRepository $repository,
        array $prData,
        bool $autoLink = true,
        ?string $action = null,
    ): GitHubPullRequest {
        $pr = GitHubPullRequest::updateOrCreate(
            [
                'github_repository_id' => $repository->id,
                'github_pr_id' => $prData['id'],
            ],
            [
                'number' => $prData['number'],
                'title' => $prData['title'] ?? '',
                'body' => $prData['body'] ?? null,
                'html_url' => $prData['html_url'] ?? '',
                'state' => $prData['state'] ?? 'open',
                'is_merged' => (bool) ($prData['merged'] ?? false),
                'draft' => (bool) ($prData['draft'] ?? false),
                'head_ref' => $prData['head']['ref'] ?? null,
                'base_ref' => $prData['base']['ref'] ?? null,
                'head_sha' => $prData['head']['sha'] ?? null,
                'base_sha' => $prData['base']['sha'] ?? null,
                'author_login' => $prData['user']['login'] ?? null,
                'opened_at' => $this->parseTime($prData['created_at'] ?? null),
                'merged_at' => $this->parseTime($prData['merged_at'] ?? null),
                'closed_at' => $this->parseTime($prData['closed_at'] ?? null),
                'last_synced_at' => now(),
            ]
        );

        if ($autoLink) {
            // Auto-link issues referenced in the PR title, description, or branch name.
            $this->linking->linkFromReferenceText(
                $pr,
                trim(($pr->title ?? '') . "\n" . ($pr->body ?? '') . "\n" . ($pr->head_ref ?? '')),
                GitHubLinkSource::PR_DESCRIPTION,
            );
        }

        event(new PullRequestSynced($pr, $action ?? 'synchronize'));

        return $pr;
    }

    /**
     * Backfill a repository's open pull requests from the GitHub API.
     *
     * @return int  Number of pull requests fetched.
     */
    public function syncRepositoryPullRequests(GitHubRepository $repository): int
    {
        $installation = $repository->installation;

        if (! $installation || $installation->status !== 'active') {
            return 0;
        }

        $pullRequests = $this->client->getRepositoryPullRequests(
            $installation->github_installation_id,
            $repository->full_name,
            'open',
        );

        foreach ($pullRequests as $prData) {
            if (empty($prData['id']) || empty($prData['number'])) {
                continue;
            }

            $this->upsertPullRequest($repository, $prData, autoLink: true, action: 'backfill');
        }

        return count($pullRequests);
    }

    private function parseTime(?string $time): ?\DateTimeInterface
    {
        return $time ? Carbon::parse($time) : null;
    }
}
