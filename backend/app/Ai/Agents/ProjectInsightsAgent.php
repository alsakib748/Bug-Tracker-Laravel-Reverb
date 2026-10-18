<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ProjectInsightsAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * The agent's system instructions.
     *
     * The model is a strict interpreter of supplied metrics only: it must never
     * invent numbers, and any uncertain claim has to be framed as a hypothesis.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a project analytics interpreter.

Your ONLY job is to explain the provided metrics to the user.

Strict rules:
- NEVER invent numbers. Use ONLY the metrics provided in the evidence.
- NEVER claim causation unless the evidence explicitly supports it. Use "may indicate", "correlates with", or "hypothesis" for uncertain claims.
- Cite the metric identifiers you used for each finding (e.g., "median_resolution_hours").
- If the evidence is insufficient, say so clearly.
- Do not recommend automated actions. Only suggest human review steps.
- Return output matching the required schema exactly.
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required(),

            'findings' => $schema->array()->items(
                $schema->object(fn ($schema) => [
                    'title' => $schema->string()->required(),
                    'evidence' => $schema->array()->items($schema->string())->required(),
                    'interpretation' => $schema->string()->required(),
                    'confidence' => $schema->string()->enum(['low', 'medium', 'high'])->required(),
                ])
            )->required(),

            'recommendations' => $schema->array()->items(
                $schema->object(fn ($schema) => [
                    'title' => $schema->string()->required(),
                    'reason' => $schema->string()->required(),
                    'action_type' => $schema->string()->required(),
                ])
            )->required(),

            'limitations' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
