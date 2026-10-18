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

        return <<<PROMPT
Insight type: {$insightType}

EVIDENCE (only these metrics may be used):

{$json}

Generate a project insight matching the schema. Cite metric identifiers from the evidence for each finding. Do not invent numbers.
PROMPT;
    }
}
