<?php

namespace App\Services\Insights;

use App\Ai\Agents\ProjectInsightsAgent;

class InsightGenerator
{
    /**
     * Run the insight agent against the evidence package.
     *
     * @param  array<string, mixed>  $evidence
     * @return array{result: array<string, mixed>, provider: ?string, model: ?string, usage: mixed}
     */
    public function generate(array $evidence, string $insightType): array
    {
        $prompt = $this->buildPrompt($evidence, $insightType);

        $response = (new ProjectInsightsAgent)->prompt($prompt);

        return [
            'result' => [
                'summary' => $response['summary'] ?? '',
                'findings' => $response['findings'] ?? [],
                'recommendations' => $response['recommendations'] ?? [],
                'limitations' => $response['limitations'] ?? [],
            ],
            'provider' => $response->meta?->provider ?? config('ai.default'),
            'model' => $response->meta?->model ?? config('ai.model'),
            'usage' => $response->usage ?? null,
        ];
    }

    /**
     * Build the single, self-contained prompt sent to the provider.
     *
     * @param  array<string, mixed>  $evidence
     */
    protected function buildPrompt(array $evidence, string $insightType): string
    {
        $json = json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $specific = match ($insightType) {
            'github_health' => <<<'EXTRA'
Focus specifically on GitHub development velocity, using only metrics under "github":
- If prs_awaiting_review.count is greater than 3, flag it as a possible review bottleneck.
- If time_to_first_pr.median_hours is greater than 48, flag it as possible work-initiation delay.
- If issues_without_pr is greater than 5, flag it as work not yet started.
- If a metric's sample_size is 0, state that there is not enough data instead of interpreting it.
- NEVER invent PR counts, author names, branches, or PR titles.
- Cite the exact metric identifiers (e.g. "github.prs_awaiting_review.count") for every claim.
EXTRA,
            default => '',
        };

        return <<<PROMPT
Insight type: {$insightType}

EVIDENCE (only these metrics may be used):

{$json}

{$specific}

Generate a project insight matching the schema. Cite metric identifiers from the evidence for each finding. Do not invent numbers.
PROMPT;
    }
}
