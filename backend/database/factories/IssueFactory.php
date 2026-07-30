<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $statuses = ['open', 'assigned', 'in_progress', 'code_review', 'testing', 'resolved', 'closed', 'reopened'];
        $priorities = ['low', 'medium', 'high', 'critical'];
        $severities = ['minor', 'major', 'critical', 'blocker'];
        $types = ['bug', 'feature', 'improvement', 'task'];

        return [
            'project_id' => Project::factory(),
            'reporter_id' => User::factory(),
            'assigned_to' => null, // will be set in seeder
            'title' => fake()->sentence(5),
            'description' => fake()->paragraphs(3, true),
            'priority' => fake()->randomElement($priorities),
            'severity' => fake()->randomElement($severities),
            'status' => fake()->randomElement($statuses),
            'type' => fake()->randomElement($types),
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'estimated_hours' => fake()->randomFloat(1, 0.5, 40),
            'completed_at' => null, // set later if status is resolved/closed
        ];
    }

    // Helper to set specific status
    public function status($status): static
    {
        return $this->state(fn() => ['status' => $status]);
    }

    public function assigned(): static
    {
        return $this->state(fn() => [
            'status' => 'assigned',
            'assigned_to' => User::factory(),
        ]);
    }

}