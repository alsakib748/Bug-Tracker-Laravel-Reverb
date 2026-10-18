<?php

namespace App\Integrations\GitHub\Services;

use App\Integrations\GitHub\Jobs\ProcessGitHubWebhook;
use App\Models\GitHubWebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitHubWebhookService
{
    private const PAYLOAD_DIR = 'app/github-webhooks';

    /**
     * Verify the webhook signature using the raw request body.
     *
     * GitHub signs the exact bytes it sent — HMAC must be computed on
     * $request->getContent(), never on parsed/re-serialized JSON.
     */
    public function verifySignature(Request $request): bool
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256');
        $secret = config('services.github.webhook_secret');

        if (! is_string($signature) || ! is_string($secret) || $secret === '') {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Record a delivery (deduplicating on X-GitHub-Delivery) and queue processing.
     */
    public function handleDelivery(Request $request): GitHubWebhookDelivery
    {
        $deliveryId = $request->header('X-GitHub-Delivery');
        $eventType = $request->header('X-GitHub-Event');
        $payload = json_decode($request->getContent(), true) ?? [];

        $delivery = GitHubWebhookDelivery::firstOrCreate(
            ['github_delivery_id' => $deliveryId],
            [
                'github_installation_id' => $payload['installation']['id'] ?? null,
                'github_repository_id' => $payload['repository']['id'] ?? null,
                'event_type' => $eventType,
                'action' => $payload['action'] ?? null,
                'payload_summary' => $this->summarize($eventType, $payload),
                'status' => 'received',
                'received_at' => now(),
            ]
        );

        // GitHub has no timestamp header, so replay defense is the unique
        // delivery UUID: only a freshly received delivery is ever queued.
        if ($delivery->status === 'received') {
            $this->persistPayload($delivery->id, $request->getContent());

            ProcessGitHubWebhook::dispatch($delivery->id)->afterCommit();
        }

        return $delivery;
    }

    /**
     * Store the raw payload outside the DB so queued jobs can process it
     * without keeping full payloads in the database (privacy + storage).
     */
    public function persistPayload(int $deliveryId, string $raw): void
    {
        $directory = storage_path(self::PAYLOAD_DIR);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($this->payloadPath($deliveryId), $raw);
    }

    /**
     * Read a previously persisted payload. Returns null when missing/corrupt.
     */
    public function readPayload(int $deliveryId): ?array
    {
        $path = $this->payloadPath($deliveryId);

        if (! is_readable($path)) {
            return null;
        }

        $payload = json_decode((string) file_get_contents($path), true);

        return is_array($payload) ? $payload : null;
    }

    /**
     * Delete the persisted payload once the delivery is fully processed.
     */
    public function forgetPayload(int $deliveryId): void
    {
        $path = $this->payloadPath($deliveryId);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    protected function payloadPath(int $deliveryId): string
    {
        return storage_path(self::PAYLOAD_DIR . '/' . $deliveryId . '.json');
    }

    /**
     * Keep only non-sensitive fields for the DB record.
     */
    protected function summarize(string $eventType, array $payload): array
    {
        return array_filter([
            'action' => $payload['action'] ?? null,
            'repository' => $payload['repository']['full_name'] ?? null,
            'sender' => $payload['sender']['login'] ?? null,
            'installation_id' => $payload['installation']['id'] ?? null,
            'event' => $eventType,
        ]);
    }
}
