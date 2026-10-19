<?php

namespace Tests\Feature;

use App\Models\GitHubWebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GitHubWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.github.webhook_secret', self::SECRET);

        // Never run the processing job (it talks to the GitHub API) in tests.
        Queue::fake();
    }

    protected function tearDown(): void
    {
        foreach (GitHubWebhookDelivery::query()->pluck('id') as $id) {
            $path = storage_path('app/github-webhooks/' . $id . '.json');

            if (is_file($path)) {
                File::delete($path);
            }
        }

        parent::tearDown();
    }

    private function payload(): string
    {
        return (string) json_encode([
            'action' => 'opened',
            'installation' => ['id' => 12345],
            'repository' => ['id' => 99, 'full_name' => 'acme/widgets'],
            'sender' => ['login' => 'octocat'],
        ]);
    }

    private function signature(string $body): string
    {
        return 'sha256=' . hash_hmac('sha256', $body, self::SECRET);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    private function headers(string $body, array $overrides = []): array
    {
        return array_merge([
            'HTTP_X_HUB_SIGNATURE_256' => $this->signature($body),
            'HTTP_X_GITHUB_EVENT' => 'issues',
            'HTTP_X_GITHUB_DELIVERY' => 'delivery-uuid-1',
            'CONTENT_TYPE' => 'application/json',
        ], $overrides);
    }

    public function test_valid_signature_is_accepted_and_delivery_is_recorded(): void
    {
        $body = $this->payload();

        $this->call('POST', '/api/webhooks/github', [], [], [], $this->headers($body), $body)
            ->assertStatus(202);

        $this->assertDatabaseHas('github_webhook_deliveries', [
            'github_delivery_id' => 'delivery-uuid-1',
            'event_type' => 'issues',
            'action' => 'opened',
            'github_installation_id' => 12345,
            'github_repository_id' => 99,
        ]);
    }

    public function test_invalid_signature_is_rejected_and_nothing_is_recorded(): void
    {
        $body = $this->payload();

        $this->call('POST', '/api/webhooks/github', [], [], [], $this->headers($body, [
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=deadbeef',
        ]), $body)->assertStatus(401);

        $this->assertDatabaseCount('github_webhook_deliveries', 0);
    }

    public function test_missing_signature_is_rejected(): void
    {
        $body = $this->payload();

        $headers = $this->headers($body);
        unset($headers['HTTP_X_HUB_SIGNATURE_256']);

        $this->call('POST', '/api/webhooks/github', [], [], [], $headers, $body)
            ->assertStatus(401);

        $this->assertDatabaseCount('github_webhook_deliveries', 0);
    }

    public function test_duplicate_delivery_is_deduplicated(): void
    {
        $body = $this->payload();

        $this->call('POST', '/api/webhooks/github', [], [], [], $this->headers($body), $body)
            ->assertStatus(202);

        $this->call('POST', '/api/webhooks/github', [], [], [], $this->headers($body), $body)
            ->assertStatus(202);

        $this->assertDatabaseCount('github_webhook_deliveries', 1);
    }

    public function test_malformed_payload_does_not_crash_the_endpoint(): void
    {
        $body = 'not-json';

        $response = $this->call('POST', '/api/webhooks/github', [], [], [], $this->headers($body), $body);

        $this->assertContains($response->getStatusCode(), [202, 400, 422]);
    }
}
