<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectInsight;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectInsight>
 */
class ProjectInsightFactory extends Factory
{
    protected $model = ProjectInsight::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'requested_by' => User::factory(),
            'insight_type' => 'project_summary',
            'period_start' => now()->subDays(30)->toDateString(),
            'period_end' => now()->toDateString(),
            'status' => 'queued',
            'filters' => ['timezone' => 'UTC'],
            'prompt_version' => 'v1',
        ];
    }
}
