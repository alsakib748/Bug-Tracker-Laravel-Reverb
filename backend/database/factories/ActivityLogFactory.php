<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['created', 'updated', 'assigned', 'status_changed', 'commented', 'attached'];
        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'issue_id' => Issue::factory(),
            'action' => fake()->randomElement($actions),
            'description' => fake()->sentence(),
            'ip' => fake()->ipv4(),
        ];
    }
}