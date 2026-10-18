<?php

namespace App\Integrations\GitHub\Jobs;

use App\Enums\GitHubLinkSource;
use App\Integrations\GitHub\Services\GitHubInstallationService;
use App\Integrations\GitHub\Services\GitHubLinkingService;
use App\Integrations\GitHub\Services\GitHubPullRequestSyncService;
use App\Integrations\GitHub\Services\GitHubWebhookService;
use App\Models\GitHubCheckRun;
use App\Models\GitHubCommit;
use App\Models\GitHubRepository;
use App\Models\GitHubWebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGitHubWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $deliveryId) {}

    public function handle(
        GitHubWebhookService $webhooks,
        GitHubInstallationService $installations,
        GitHubLinkingService $linking,
        GitHubPullRequestSyncService $pullRequests,
    ): void {
        $delivery = GitHubWebhookDelivery::find($this->deliveryId);

        if (! $delivery || in_array($delivery->status, ['processed', 'ignored'], true)) {
            return;
        }

        $delivery->update(['status' => 'queued', 'attempt_count' => $delivery->attempt_count + 1]);

        $payload = $webhooks->readPayload($delivery->id);

        if ($payload === null) {
            $delivery->update([
                'status' => 'failed',
                'error_code' => 'payload_missing',
                'processed_at' => now(),
            ]);

            return;
        }

        try {
            $outcome = match ($delivery->event_type) {
                'pull_request' => $this->handlePullRequest($delivery, $payload, $pullRequests),
                'push' => $this->handlePush($delivery, $payload, $linking),
                'installation' => $this->handleInstallation($delivery, $payload, $installations),
                'installation_repositories' => $this->handleInstallationRepositories($delivery, $payload, $installations),
                'check_suite', 'check_run' => $this->handleChecks($delivery, $payload),
                default => 'ignored',
            };

            $delivery->update([
                'status' => $outcome,
                'error_code' => null,
                'processed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $delivery->update([
                'status' => 'failed',
                'error_code' => substr($exception->getMessage(), 0, 100),
            ]);

            Log::error('GitHub webhook processing failed', [
                'delivery_id' => $delivery->github_delivery_id,
                'event' => $delivery->event_type,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            $webhooks->forgetPayload($delivery->id);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        GitHubWebhookDelivery::whereKey($this->deliveryId)
            ->where('status', '!=', 'processed')
            ->update([
                'status' => 'failed',
                'error_code' => substr($exception?->getMessage() ?? 'unknown', 0, 100),
                'processed_at' => now(),
            ]);
    }

    protected function handlePullRequest(
        GitHubWebhookDelivery $delivery,
        array $payload,
        GitHubPullRequestSyncService $sync,
    ): string {
        $repoData = $payload['repository'] ?? null;
        $prData = $payload['pull_request'] ?? null;

        if (! $repoData || ! $prData) {
            return 'ignored';
        }

        $repository = GitHubRepository::where('github_repository_id', $repoData['id'])->first();

        if (! $repository) {
            return 'ignored';
        }

        $sync->upsertPullRequest(
            $repository,
            $prData,
            autoLink: true,
            action: (string) ($payload['action'] ?? 'synchronize'),
        );

        return 'processed';
    }

    protected function handlePush(
        GitHubWebhookDelivery $delivery,
        array $payload,
        GitHubLinkingService $linking,
    ): string {
        $repoData = $payload['repository'] ?? null;
        $commits = $payload['commits'] ?? [];

        if (! $repoData) {
            return 'ignored';
        }

        $repository = GitHubRepository::where('github_repository_id', $repoData['id'])->first();

        if (! $repository) {
            return 'ignored';
        }

        foreach ($commits as $commit) {
            if (! isset($commit['id'], $commit['message'])) {
                continue;
            }

            $record = GitHubCommit::updateOrCreate(
                [
                    'github_repository_id' => $repository->id,
                    'sha' => $commit['id'],
                ],
                [
                    'message_summary' => mb_substr($commit['message'], 0, 500),
                    'author_login' => $commit['author']['name'] ?? null,
                    'committed_at' => $this->parseTime($commit['timestamp'] ?? null),
                    'html_url' => $commit['url'] ?? null,
                ]
            );

            // Issue references ("Fixes #12", "LMS-12") in commit messages
            // become links.
            $issues = $linking->resolveIssues($repository, $commit['message']);

            foreach ($issues as $issue) {
                $linking->linkCommit($issue, [
                    'sha' => $record->sha,
                    'html_url' => $record->html_url,
                ], $repository->id, null, GitHubLinkSource::COMMIT_MESSAGE);
            }
        }

        return 'processed';
    }

    protected function handleInstallation(
        GitHubWebhookDelivery $delivery,
        array $payload,
        GitHubInstallationService $installations,
    ): string {
        $installationId = $payload['installation']['id'] ?? null;

        if (! $installationId) {
            return 'ignored';
        }

        match ($payload['action'] ?? null) {
            'created', 'new_permissions_accepted' => $installations->verifyInstallation((int) $installationId),
            'suspend' => $installations->updateInstallationStatus((int) $installationId, 'suspended'),
            'unsuspend' => $installations->updateInstallationStatus((int) $installationId, 'active'),
            'deleted' => $installations->updateInstallationStatus((int) $installationId, 'disconnected'),
            default => null,
        };

        return 'processed';
    }

    protected function handleInstallationRepositories(
        GitHubWebhookDelivery $delivery,
        array $payload,
        GitHubInstallationService $installations,
    ): string {
        $installationId = $payload['installation']['id'] ?? null;

        if (! $installationId) {
            return 'ignored';
        }

        $installations->verifyInstallation((int) $installationId);

        return 'processed';
    }

    protected function handleChecks(
        GitHubWebhookDelivery $delivery,
        array $payload,
    ): string {
        $repositoryId = $payload['repository']['id'] ?? null;
        $repository = $repositoryId
            ? GitHubRepository::where('github_repository_id', $repositoryId)->first()
            : null;

        if ($repository) {
            $repository->update(['last_synced_at' => now()]);
        }

        // check_run events carry a single check_run; check_suite events may
        // embed check_runs (often empty — the dedicated check_run events then
        // fill the table in).
        $checkRuns = array_merge(
            isset($payload['check_run']) ? [$payload['check_run']] : [],
            $payload['check_suite']['check_runs'] ?? [],
        );

        foreach ($checkRuns as $checkRun) {
            if (! $repository || empty($checkRun['id']) || empty($checkRun['head_sha'])) {
                continue;
            }

            GitHubCheckRun::updateOrCreate(
                [
                    'github_repository_id' => $repository->id,
                    'github_check_run_id' => $checkRun['id'],
                ],
                [
                    'name' => $checkRun['name'] ?? 'check',
                    'head_sha' => $checkRun['head_sha'],
                    'status' => $checkRun['status'] ?? 'completed',
                    'conclusion' => $checkRun['conclusion'] ?? null,
                    'html_url' => $checkRun['html_url'] ?? $checkRun['details_url'] ?? null,
                    'started_at' => $this->parseTime($checkRun['started_at'] ?? null),
                    'completed_at' => $this->parseTime($checkRun['completed_at'] ?? null),
                ]
            );
        }

        return 'processed';
    }

    protected function parseTime(?string $time): ?\DateTimeInterface
    {
        return $time ? \Illuminate\Support\Carbon::parse($time) : null;
    }
}
