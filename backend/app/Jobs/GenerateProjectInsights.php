<?php

namespace App\Jobs;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Models\ProjectInsight;
use App\Services\Insights\InsightContextBuilder;
use App\Services\Insights\InsightGenerator;
use App\Services\Insights\InsightValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;

/**
 * Generate an AI project insight for an already persisted request.
 *
 * Deterministic metrics are snapshotted before the model is called, so the
 * insight always pairs an interpretation with the evidence it was based on.
 */
class GenerateProjectInsights implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * Seconds to wait before each retry after an API error.
     *
     * @var array<int, int>
     */
    public array $backoff = [30, 90];

    public function __construct(public int $insightId) {}

    public function handle(
        InsightContextBuilder $contextBuilder,
        InsightGenerator $generator,
        InsightValidator $validator,
    ): void {
        $insight = ProjectInsight::find($this->insightId);

        if (! $insight || ! in_array($insight->status, ['queued', 'running'], true)) {
            return;
        }

        $insight->update(['status' => 'running']);

        try {
            $filters = new AnalyticsFilters(
                projectId: $insight->project_id,
                periodStart: $insight->period_start->copy()->startOfDay(),
                periodEnd: $insight->period_end->copy()->endOfDay(),
                timezone: $insight->filters['timezone'] ?? 'UTC',
            );

            $evidence = $contextBuilder->build($filters, $insight->insight_type);
            $generated = $generator->generate($evidence, $insight->insight_type);
            $validated = $validator->validate($generated['result'], $evidence['metrics']);

            $insight->update([
                'status' => 'completed',
                'metrics_snapshot' => $evidence['metrics'],
                'result_json' => $validated,
                'model_provider' => $generated['provider'],
                'model_name' => $generated['model'],
                'error_code' => null,
                'generated_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);
        } catch (RateLimitedException|InsufficientCreditsException|ProviderOverloadedException|ProviderConnectionException $exception) {
            // Retrying within the backoff window will not help and would only
            // burn limited free-tier quota, so fail fast and stop retrying.
            $insight->update([
                'status' => 'failed',
                'error_code' => $this->errorCodeFor($exception),
            ]);

            Log::warning('Project insight generation stopped', [
                'insight_id' => $this->insightId,
                'error' => $exception->getMessage(),
            ]);
        } catch (\Throwable $exception) {
            // Leave the record "running" so a queued retry can attempt again;
            // the failed() hook finalises the status once retries are exhausted.
            Log::error('Project insight generation failed', [
                'insight_id' => $this->insightId,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Project insight generation exhausted retries', [
            'insight_id' => $this->insightId,
            'error' => $exception?->getMessage(),
        ]);

        ProjectInsight::whereKey($this->insightId)
            ->whereIn('status', ['queued', 'running'])
            ->update([
                'status' => 'failed',
                'error_code' => $this->errorCodeFor($exception),
            ]);
    }

    /**
     * Map a provider exception to a stable, user-facing error code.
     */
    protected function errorCodeFor(?\Throwable $exception): string
    {
        return match (true) {
            $exception instanceof RateLimitedException => 'provider_rate_limited',
            $exception instanceof InsufficientCreditsException => 'insufficient_credits',
            $exception instanceof ProviderOverloadedException => 'provider_overloaded',
            $exception instanceof ProviderConnectionException => 'provider_unreachable',
            default => 'generation_failed',
        };
    }
}
