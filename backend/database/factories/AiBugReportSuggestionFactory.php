<?php

namespace Database\Factories;

use App\Models\AiBugReportSuggestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiBugReportSuggestion>
 */
class AiBugReportSuggestionFactory extends Factory
{
    protected $model = AiBugReportSuggestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'issue_id' => null,
            'requested_by' => User::factory(),
            'status' => 'queued',
            'input_snapshot' => [
                'title' => fake()->sentence(4),
                'description' => fake()->paragraph(),
            ],
            'input_hash' => hash('sha256', fake()->uuid()),
            'suggestions' => null,
            'provider' => null,
            'model' => null,
            'expires_at' => now()->addHours(24),
        ];
    }

    public function ready(): static
    {
        return $this->state(fn () => [
            'status' => 'ready',
            'suggestions' => [
                'improved_title' => 'Improved title',
                'improved_description' => 'Improved description',
                'suggested_priority' => 'high',
                'priority_reason' => 'Because it blocks users.',
                'confidence' => 'medium',
                'missing_information' => [],
                'suggested_labels' => [],
                'questions_for_reporter' => [],
            ],
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error_code' => 'provider_error',
            'completed_at' => now(),
        ]);
    }
}
