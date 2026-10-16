<?php

namespace App\Services\Ai;

use App\Ai\Agents\BugReportAssistant;
use App\Models\AiBugReportSuggestion;
use App\Models\Project;

class BugReportAssistantService
{
    public function __construct(
        private readonly BugReportContextBuilder $contextBuilder,
        private readonly BugReportSuggestionValidator $validator,
    ) {}

    /**
     * Analyze a stored analysis request and return validated suggestions.
     *
     * @return array{suggestions: array<string, mixed>, input_tokens: ?int, output_tokens: ?int, provider: ?string, model: ?string}
     */
    public function analyze(AiBugReportSuggestion $analysis): array
    {
        $project = Project::findOrFail($analysis->project_id);

        $context = $this->contextBuilder->build($analysis->input_snapshot ?? [], $project);

        $prompt = $this->buildPrompt($context);

        $response = (new BugReportAssistant)->prompt($prompt);

        $suggestions = [
            'improved_title' => $response['improved_title'] ?? '',
            'improved_description' => $response['improved_description'] ?? '',
            'suggested_priority' => $response['suggested_priority'] ?? 'medium',
            'priority_reason' => $response['priority_reason'] ?? '',
            'confidence' => $response['confidence'] ?? 'medium',
            'missing_information' => $response['missing_information'] ?? [],
            'suggested_labels' => $response['suggested_labels'] ?? [],
            'questions_for_reporter' => $response['questions_for_reporter'] ?? [],
        ];

        $validated = $this->validator->validate($suggestions, $project);

        return [
            'suggestions' => $validated,
            'usage' => $response->usage,
            'provider' => $response->meta->provider,
            'model' => $response->meta->model,
        ];
    }

    /**
     * Build the single, self-contained prompt sent to the provider.
     *
     * @param  array<string, mixed>  $context
     */
    protected function buildPrompt(array $context): string
    {
        $report = $context['report'];

        $priorities = implode(', ', $context['allowed_priorities']);
        $labels = collect($context['available_labels'])
            ->map(fn ($label) => "ID {$label['id']}: {$label['name']}"
                . ($label['description'] ? " - {$label['description']}" : ''))
            ->implode("\n");

        $labels = $labels !== '' ? $labels : '(no labels defined for this project)';

        $steps = $this->formatSteps($report['reproduction_steps']);

        return <<<PROMPT
        Analyze the bug report between the <report> tags and return improvement suggestions.

        <report>
        Title: {$report['title']}
        Description: {$report['description']}
        Current type: {$this->orNone($report['type'])}
        Current severity: {$this->orNone($report['severity'])}
        Current priority: {$this->orNone($report['current_priority'])}
        Expected behavior: {$this->orNone($report['expected_behavior'])}
        Actual behavior: {$this->orNone($report['actual_behavior'])}
        Reproduction steps:
        {$steps}
        Environment: {$this->orNone($report['environment'])}
        </report>

        Allowed priorities: {$priorities}

        Available project labels:
        {$labels}

        Return suggestions matching the required schema. Only suggest labels from the list above.
        PROMPT;
    }

    protected function orNone(?string $value): string
    {
        return ($value === null || trim($value) === '') ? 'Not provided' : $value;
    }

    protected function formatSteps(mixed $steps): string
    {
        if (is_array($steps) && $steps !== []) {
            return implode("\n", array_map(fn ($step) => "- {$step}", $steps));
        }

        return '- Not provided';
    }
}
