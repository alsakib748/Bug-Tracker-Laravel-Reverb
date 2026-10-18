<?php

namespace App\Integrations\GitHub\Clients;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubApiClient
{
    private const BASE_URL = 'https://api.github.com';

    private const API_VERSION = '2022-11-28';

    /**
     * Get an installation access token (cached for 55 min).
     */
    public function getInstallationToken(int $installationId): string
    {
        return Cache::remember("github_installation_token:{$installationId}", 55 * 60, function () use ($installationId) {
            $jwt = $this->generateJwt();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $jwt,
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => self::API_VERSION,
            ])->post(self::BASE_URL . "/app/installations/{$installationId}/access_tokens");

            if (! $response->successful()) {
                Log::warning('GitHub installation token request failed', [
                    'installation_id' => $installationId,
                    'status' => $response->status(),
                ]);

                throw new \RuntimeException('Failed to get GitHub installation token: ' . $response->status());
            }

            return $response->json('token');
        });
    }

    /**
     * Generate a JWT for GitHub App authentication (RS256).
     */
    public function generateJwt(): string
    {
        $privateKeyPath = $this->privateKeyPath();

        if (! is_readable($privateKeyPath)) {
            throw new \RuntimeException('GitHub App private key is not readable: ' . $privateKeyPath);
        }

        $privateKey = file_get_contents($privateKeyPath);
        $appId = config('services.github.app_id');

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        // GitHub rejects exp more than 10 minutes ahead of ITS clock; keep a
        // margin for clock skew (iat backdated 60s, exp 9 minutes ahead).
        $payload = [
            'iat' => time() - 60,
            'exp' => time() + 540,
            'iss' => (string) $appId,
        ];

        $base64UrlHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $base64UrlPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $signatureInput = $base64UrlHeader . '.' . $base64UrlPayload;

        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $signatureInput . '.' . $base64UrlSignature;
    }

    /**
     * Fetch a single installation (used to verify after App install redirect).
     */
    public function getInstallation(int $installationId): ?array
    {
        $jwt = $this->generateJwt();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $jwt,
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => self::API_VERSION,
        ])->get(self::BASE_URL . "/app/installations/{$installationId}");

        if (! $response->successful()) {
            Log::warning('GitHub installation lookup failed', [
                'installation_id' => $installationId,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Fetch repositories accessible by an installation (paginated, up to 10 pages / 1000 repos).
     */
    public function getInstallationRepositories(int $installationId): array
    {
        $token = $this->getInstallationToken($installationId);
        $repositories = [];
        $page = 1;
        $perPage = 100;

        do {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => self::API_VERSION])
                ->get(self::BASE_URL . '/installation/repositories', [
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

            if (! $response->successful()) {
                Log::warning('GitHub repository listing failed', [
                    'installation_id' => $installationId,
                    'status' => $response->status(),
                ]);

                break;
            }

            $batch = $response->json('repositories', []);
            $repositories = array_merge($repositories, $batch);

            $hasMore = count($batch) === $perPage;
            $page++;
        } while ($hasMore && $page <= 10);

        return $repositories;
    }

    /**
     * Fetch a single PR from GitHub.
     */
    public function getPullRequest(int $installationId, string $repoFullName, int $prNumber): ?array
    {
        $token = $this->getInstallationToken($installationId);

        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => self::API_VERSION])
            ->get(self::BASE_URL . "/repos/{$repoFullName}/pulls/{$prNumber}");

        if (! $response->successful()) {
            Log::warning('GitHub PR fetch failed', [
                'repo' => $repoFullName,
                'pr' => $prNumber,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Fetch a repository's pull requests (paginated, up to 3 pages / 300 PRs).
     */
    public function getRepositoryPullRequests(int $installationId, string $repoFullName, string $state = 'open'): array
    {
        $token = $this->getInstallationToken($installationId);
        $pullRequests = [];
        $page = 1;
        $perPage = 100;

        do {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => self::API_VERSION])
                ->get(self::BASE_URL . "/repos/{$repoFullName}/pulls", [
                    'state' => $state,
                    'sort' => 'updated',
                    'direction' => 'desc',
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

            if (! $response->successful()) {
                Log::warning('GitHub PR listing failed', [
                    'repo' => $repoFullName,
                    'state' => $state,
                    'status' => $response->status(),
                ]);

                break;
            }

            $batch = $response->json();

            if (! is_array($batch)) {
                break;
            }

            $pullRequests = array_merge($pullRequests, $batch);

            $hasMore = count($batch) === $perPage;
            $page++;
        } while ($hasMore && $page <= 3);

        return $pullRequests;
    }

    /**
     * Fetch check runs for a commit.
     */
    public function getCheckRuns(int $installationId, string $repoFullName, string $ref): array
    {
        $token = $this->getInstallationToken($installationId);

        $response = Http::withToken($token)
            ->withHeaders(['Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => self::API_VERSION])
            ->get(self::BASE_URL . "/repos/{$repoFullName}/commits/{$ref}/check-runs");

        return $response->json('check_runs', []);
    }
    /**
     * Drop a cached installation token (e.g. after it expires mid-request).
     */
    public function forgetInstallationToken(int $installationId): void
    {
        Cache::forget("github_installation_token:{$installationId}");
    }

    /**
     * Resolve the private key path, tolerating project-relative paths like
     * "storage/app/key.pem" in .env regardless of the current working directory.
     */
    private function privateKeyPath(): string
    {
        $path = (string) config('services.github.private_key_path');

        if ($path !== '' && ! preg_match('#^(?:[A-Za-z]:[\\\\/]|/)#', $path)) {
            $path = base_path($path);
        }

        return $path;
    }
}
