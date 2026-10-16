<?php

namespace App\Services\Ai;

use App\Enums\Priority;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

class BugReportSuggestionValidator
{
    /**
     * Validate and clamp the structured output returned by the agent.
     *
     * The schema already guarantees the shape, but we re-check every value
     * against our own application rules (especially labels / priorities) so a
     * misbehaving or manipulated model can never smuggle in invalid data.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $suggestions, Project $project): array
    {
        $title = $suggestions['improved_title'] ?? null;

        if (! is_string($title) || trim($title) === '') {
            throw ValidationException::withMessages([
                'suggestions' => 'The AI did not return a usable title suggestion.',
            ]);
        }

        $description = $suggestions['improved_description'] ?? null;

        if (! is_string($description) || trim($description) === '') {
            throw ValidationException::withMessages([
                'suggestions' => 'The AI did not return a usable description suggestion.',
            ]);
        }

        $allowedPriorities = array_column(Priority::cases(), 'value');
        $priority = $suggestions['suggested_priority'] ?? null;

        if (! in_array($priority, $allowedPriorities, true)) {
            $priority = Priority::MEDIUM->value;
        }

        $validLabelIds = $project->labels()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $suggestedLabels = is_array($suggestions['suggested_labels'] ?? null)
            ? $suggestions['suggested_labels']
            : [];

        $validatedLabels = [];

        foreach ($suggestedLabels as $label) {
            $labelId = isset($label['label_id']) ? (int) $label['label_id'] : null;
            $reason = $label['reason'] ?? null;

            if ($labelId === null || ! is_string($reason) || trim($reason) === '') {
                continue;
            }

            // Reject any label that does not belong to this project.
            if (! in_array($labelId, $validLabelIds, true)) {
                continue;
            }

            $validatedLabels[$labelId] = [
                'label_id' => $labelId,
                'reason' => $this->clamp($reason, 500),
            ];
        }

        $confidence = $suggestions['confidence'] ?? 'medium';

        if (! in_array($confidence, ['low', 'medium', 'high'], true)) {
            $confidence = 'medium';
        }

        $missingInformation = is_array($suggestions['missing_information'] ?? null)
            ? array_slice($suggestions['missing_information'], 0, 10)
            : [];

        $missingInformation = array_map(fn ($item) => [
            'field' => is_string($item['field'] ?? null) ? $this->clamp($item['field'], 100) : 'unknown',
            'reason' => is_string($item['reason'] ?? null) ? $this->clamp($item['reason'], 500) : '',
        ], $missingInformation);

        $questions = is_array($suggestions['questions_for_reporter'] ?? null)
            ? array_slice($suggestions['questions_for_reporter'], 0, 10)
            : [];

        $questions = array_values(array_filter(array_map(
            fn ($question) => is_string($question) ? $this->clamp($question, 500) : '',
            $questions,
        ), fn ($question) => $question !== ''));

        return [
            'improved_title' => $this->clamp($title, 255),
            'improved_description' => $this->clamp($description, 10000),
            'suggested_priority' => $priority,
            'priority_reason' => is_string($suggestions['priority_reason'] ?? null)
                ? $this->clamp($suggestions['priority_reason'], 500)
                : '',
            'confidence' => $confidence,
            'missing_information' => array_values($missingInformation),
            'suggested_labels' => array_values($validatedLabels),
            'questions_for_reporter' => $questions,
        ];
    }

    protected function clamp(string $value, int $limit): string
    {
        return mb_substr(trim($value), 0, $limit);
    }
}
