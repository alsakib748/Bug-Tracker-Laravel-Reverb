<?php

namespace Database\Factories;

use App\Enums\SprintStatus;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sprint>
 */
class SprintFactory extends Factory
{
    protected $model = Sprint::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => 'Sprint ' . fake()->unique()->numerify('##'),
            'goal' => fake()->sentence(),
            'status' => SprintStatus::PLANNED->value,
            'start_date' => null,
            'end_date' => null,
            'started_at' => null,
            'completed_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => SprintStatus::ACTIVE->value,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => SprintStatus::COMPLETED->value,
            'start_date' => now()->subWeeks(2)->toDateString(),
            'end_date' => now()->toDateString(),
            'started_at' => now()->subWeeks(2),
            'completed_at' => now(),
        ]);
    }
}
