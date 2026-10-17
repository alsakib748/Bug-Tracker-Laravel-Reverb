<?php

namespace App\Jobs;

use App\Models\AiBugReportSuggestion;
use App\Services\Ai\BugReportAssistantService;
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

class AnalyzeBugReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    /**
     * Seconds to wait before each retry after an API error.
     *
     * @var array<int, int>
     */
    public array $backoff = [5, 15];

    public function __construct(public int $analysisId) {}

    public function handle(BugReportAssistantService $service): void
    {
        $analysis = AiBugReportSuggestion::find($this->analysisId);

        if (! $analysis || ! in_array($analysis->status, ['queued', 'processing'], true)) {
            return;
        }

        if ($analysis->hasExpired()) {
            $analysis->markAsExpired();

            return;
        }

        $analysis->markAsProcessing();

        try {
            $result = $service->analyze($analysis);
        } catch (RateLimitedException|InsufficientCreditsException $exception) {
            // Retrying within the backoff window will not help and would only
            // burn limited free-tier quota, so fail fast and stop retrying.
            $analysis->markAsFailed($this->errorCodeFor($exception));

            Log::warning('Bug report AI analysis stopped', [
                'analysis_id' => $this->analysisId,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $usage = $result['usage'];

        $analysis->markAsReady(
            $result['suggestions'],
            $usage?->inputTokens,
            $usage?->outputTokens,
            $result['provider'],
            $result['model'],
        );
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Bug report AI analysis failed', [
            'analysis_id' => $this->analysisId,
            'error' => $exception?->getMessage(),
        ]);

        AiBugReportSuggestion::whereKey($this->analysisId)
            ->whereNotIn('status', ['ready', 'applied', 'partially_applied', 'dismissed'])
            ->update([
                'status' => 'failed',
                'error_code' => $this->errorCodeFor($exception),
                'completed_at' => now(),
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
            default => 'provider_error',
        };
    }
}
