<?php

namespace Database\Factories;

use App\Enums\AutomationTrigger;
use App\Models\AutomationRule;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationRule>
 */
class AutomationRuleFactory extends Factory
{
    protected $model = AutomationRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'trigger_type' => AutomationTrigger::ISSUE_CREATED->value,
            'conditions' => ['match' => 'all', 'rules' => []],
            'actions' => [
                ['type' => 'change_priority', 'config' => ['priority' => 'high']],
            ],
            'is_enabled' => true,
            'run_on_actor_changes' => false,
            'created_by' => User::factory(),
            'version' => 1,
        ];
    }
}
