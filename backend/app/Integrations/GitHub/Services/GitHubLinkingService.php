<?php

namespace App\Integrations\GitHub\Services;

use App\Enums\GitHubEntityType;
use App\Enums\GitHubLinkSource;
use App\Models\GitHubIssueLink;
use App\Models\GitHubPullRequest;
use App\Models\GitHubRepository;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Collection;

class GitHubLinkingService
{
    /**
     * Link an issue to a PR.
     */
    public function linkPullRequest(
        Issue $issue,
        GitHubPullRequest $pr,
        ?int $linkedBy = null,
        GitHubLinkSource $source = GitHubLinkSource::MANUAL,
    ): GitHubIssueLink {
        $fallbackUrl = sprintf(
            'https://github.com/%s/pull/%d',
            $pr->repository->full_name,
            $pr->number,
        );

        return GitHubIssueLink::updateOrCreate(
            [
                'issue_id' => $issue->id,
                'entity_type' => GitHubEntityType::PULL_REQUEST->value,
                'github_repository_id' => $pr->github_repository_id,
                'github_entity_id' => $pr->github_pr_id,
            ],
            [
                'external_number' => $pr->number,
                'external_url' => $this->safeUrl($pr->html_url, $fallbackUrl),
                'linked_by' => $linkedBy,
                'link_source' => $source->value,
            ]
        );
    }

    /**
     * Link an issue to a commit.
     */
    public function linkCommit(
        Issue $issue,
        array $commit,
        int $repositoryId,
        ?int $linkedBy = null,
        GitHubLinkSource $source = GitHubLinkSource::COMMIT_MESSAGE,
    ): GitHubIssueLink {
        $repository = GitHubRepository::findOrFail($repositoryId);

        $fallbackUrl = sprintf(
            'https://github.com/%s/commit/%s',
            $repository->full_name,
            $commit['sha'],
        );

        return GitHubIssueLink::updateOrCreate(
            [
                'issue_id' => $issue->id,
                'entity_type' => GitHubEntityType::COMMIT->value,
                'github_repository_id' => $repositoryId,
                'external_sha' => $commit['sha'],
            ],
            [
                'external_url' => $this->safeUrl($commit['html_url'] ?? null, $fallbackUrl),
                'linked_by' => $linkedBy,
                'link_source' => $source->value,
            ]
        );
    }

    /**
     * Extract referenced issue ids ("#12") from free text.
     *
     * @return array<int, int>
     */
    public function extractIssueIds(?string $text): array
    {
        if (! $text) {
            return [];
        }

        preg_match_all('/(?:^|\s)#(\d{1,7})\b/', $text, $matches);

        return array_values(array_unique(array_map('intval', $matches[1])));
    }

    /**
     * Resolve issues referenced in free text, scoped to projects linked to the
     * repository. Supports two reference styles:
     *
     *   #12           — issue id, any project linked to the repository
     *   PROJECT-12    — project code + issue id (e.g. "LMS-42")
     *
     * @return Collection<int, Issue>
     */
    public function resolveIssues(GitHubRepository $repository, ?string $text): Collection
    {
        $projects = $repository->projects()->get(['projects.id', 'projects.code']);

        if ($projects->isEmpty() || ! $text) {
            return new Collection;
        }

        $queries = collect();

        // #12 references — issue ids scoped to every linked project.
        preg_match_all('/(?:^|[\s(])#(\d{1,7})\b/', $text, $hashMatches);
        $ids = array_values(array_unique(array_map('intval', $hashMatches[1] ?? [])));

        if ($ids !== []) {
            $queries->push(
                Issue::whereIn('id', $ids)->whereIn('project_id', $projects->pluck('id'))
            );
        }

        // CODE-12 references — project code + issue id.
        preg_match_all('/\b([A-Za-z][A-Za-z0-9]{1,9})-(\d{1,7})\b/', $text, $keyMatches);
        $codeToProject = $projects->keyBy(fn ($project) => strtoupper($project->code));
        $pairs = [];

        $matchCount = count($keyMatches[0] ?? []);

        for ($i = 0; $i < $matchCount; $i++) {
            $project = $codeToProject->get(strtoupper($keyMatches[1][$i]));

            if ($project) {
                $pairs[$project->id][] = (int) $keyMatches[2][$i];
            }
        }

        foreach ($pairs as $projectId => $issueIds) {
            $queries->push(
                Issue::whereIn('id', array_unique($issueIds))->where('project_id', $projectId)
            );
        }

        return $queries->reduce(
            fn (Collection $carry, $query) => $carry->merge($query->get()),
            new Collection,
        )->unique('id')->values();
    }

    /**
     * Link an issue to any issues referenced in the given text, scoped to the
     * projects linked to the repository.
     *
     * @return array<int>  The issue ids that were actually linked.
     */
    public function linkFromReferenceText(
        GitHubPullRequest $pr,
        ?string $text,
        GitHubLinkSource $source,
    ): array {
        $issues = $this->resolveIssues($pr->repository, $text);

        foreach ($issues as $issue) {
            $this->linkPullRequest($issue, $pr, null, $source);
        }

        return $issues->pluck('id')->all();
    }

    /**
     * Remove a link.
     */
    public function unlink(GitHubIssueLink $link): void
    {
        $link->delete();
    }

    /**
     * Only persist https://github.com/ URLs; anything else falls back to a
     * URL constructed from repository data.
     */
    private function safeUrl(?string $url, string $fallback): string
    {
        if (is_string($url) && preg_match('#^https://github\.com/#', $url)) {
            return $url;
        }

        return $fallback;
    }
}
