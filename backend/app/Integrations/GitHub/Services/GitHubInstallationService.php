<?php

namespace App\Integrations\GitHub\Services;

use App\Integrations\GitHub\Clients\GitHubApiClient;
use App\Models\GitHubInstallation;
use App\Models\GitHubRepository;
use Illuminate\Support\Facades\Log;

class GitHubInstallationService
{
    public function __construct(private readonly GitHubApiClient $client) {}

    /**
     * Verify an installation against the GitHub API and sync its repositories.
     *
     * When $account is not provided (normal redirect flow) the account data is
     * fetched server-side from GitHub so a client cannot claim a foreign org.
     */
    public function verifyInstallation(int $installationId, ?array $account = null): GitHubInstallation
    {
        $installationData = $this->client->getInstallation($installationId);

        if (! $installationData) {
            throw new \RuntimeException('GitHub installation could not be verified.');
        }

        $account = $installationData['account'] ?? $account ?? [];

        $installation = GitHubInstallation::updateOrCreate(
            ['github_installation_id' => $installationId],
            [
                'account_login' => $account['login'] ?? 'unknown',
                'account_type' => $account['type'] ?? 'User',
                'status' => 'active',
                'installed_at' => now(),
                'last_verified_at' => now(),
            ]
        );

        $this->syncRepositories($installation);

        return $installation;
    }

    /**
     * Mark an installation as suspended / disconnected / reactivated.
     */
    public function updateInstallationStatus(int $installationId, string $status): void
    {
        GitHubInstallation::where('github_installation_id', $installationId)->update([
            'status' => $status,
            'last_verified_at' => now(),
        ]);
    }

    protected function syncRepositories(GitHubInstallation $installation): void
    {
        $repos = $this->client->getInstallationRepositories($installation->github_installation_id);

        foreach ($repos as $repo) {
            GitHubRepository::updateOrCreate(
                ['github_repository_id' => $repo['id']],
                [
                    'github_installation_id' => $installation->id,
                    'owner_login' => $repo['owner']['login'] ?? '',
                    'name' => $repo['name'] ?? '',
                    'full_name' => $repo['full_name'] ?? '',
                    'html_url' => $repo['html_url'] ?? '',
                    'default_branch' => $repo['default_branch'] ?? 'main',
                    'is_private' => (bool) ($repo['private'] ?? false),
                    'status' => 'active',
                    'last_synced_at' => now(),
                ]
            );
        }

        // Repos that vanished from the installation (access revoked on GitHub side)
        // get marked unavailable instead of deleted so historical links survive.
        $activeGithubIds = array_column($repos, 'id');

        $installation->repositories()
            ->where('status', 'active')
            ->when(! empty($activeGithubIds), fn ($query) => $query->whereNotIn('github_repository_id', $activeGithubIds))
            ->when(empty($activeGithubIds), fn ($query) => $query)
            ->each(function (GitHubRepository $repository) {
                Log::info('GitHub repository no longer in installation', [
                    'repository' => $repository->full_name,
                ]);

                $repository->update(['status' => 'unavailable']);
            });
    }
}
