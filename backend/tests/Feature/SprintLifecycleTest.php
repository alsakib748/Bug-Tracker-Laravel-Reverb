<?php

namespace Tests\Feature;

use App\Enums\SprintStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SprintLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(User $creator, array $members = []): Project
    {
        $project = Project::factory()->create(['created_by' => $creator->id]);

        foreach ($members as $member) {
            $project->members()->attach($member->id, [
                'role' => 'developer',
                'joined_at' => now(),
            ]);
        }

        return $project;
    }

    private function createIssue(Project $project, int $reporterId, array $attributes = []): Issue
    {
        return Issue::create(array_merge([
            'project_id' => $project->id,
            'reporter_id' => $reporterId,
            'title' => 'Sprint issue',
            'description' => 'Sprint test issue',
            'priority' => 'medium',
            'severity' => 'major',
            'type' => 'bug',
            'status' => 'open',
        ], $attributes));
    }

    public function test_admin_can_create_a_planned_sprint(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/projects/{$project->id}/sprints", [
            'name' => 'Sprint 1',
            'goal' => 'Ship the beta',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', SprintStatus::PLANNED->value)
            ->assertJsonPath('data.name', 'Sprint 1');

        $this->assertDatabaseHas('sprints', [
            'project_id' => $project->id,
            'name' => 'Sprint 1',
            'status' => SprintStatus::PLANNED->value,
            'created_by' => $admin->id,
        ]);
    }

    public function test_only_planned_sprints_can_be_started(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $sprint = Sprint::factory()->active()->create(['project_id' => $project->id, 'created_by' => $admin->id]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/sprints/{$sprint->id}/start")
            ->assertStatus(422);

        $this->assertSame(SprintStatus::ACTIVE->value, $sprint->fresh()->status->value);
    }

    public function test_only_one_sprint_can_be_active_per_project(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        Sprint::factory()->active()->create(['project_id' => $project->id, 'created_by' => $admin->id]);
        $planned = Sprint::factory()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/sprints/{$planned->id}/start")
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertSame(SprintStatus::PLANNED->value, $planned->fresh()->status->value);
    }

    public function test_starting_a_sprint_requires_dates(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $sprint = Sprint::factory()->create(['project_id' => $project->id, 'created_by' => $admin->id]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/sprints/{$sprint->id}/start")
            ->assertStatus(422)
            ->assertJsonValidationErrors('dates');
    }

    public function test_completing_a_sprint_moves_unfinished_issues_to_backlog(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $sprint = Sprint::factory()->active()->create(['project_id' => $project->id, 'created_by' => $admin->id]);
        $issue = $this->createIssue($project, $admin->id);
        $issue->sprint_id = $sprint->id;
        $issue->save();

        Sanctum::actingAs($admin);

        $this->postJson("/api/sprints/{$sprint->id}/complete", ['carry_over' => 'backlog'])
            ->assertOk()
            ->assertJsonPath('data.status', SprintStatus::COMPLETED->value);

        $this->assertNull($issue->fresh()->sprint_id);
        $this->assertDatabaseHas('issue_sprint_histories', [
            'issue_id' => $issue->id,
            'from_sprint_id' => $sprint->id,
            'to_sprint_id' => null,
        ]);
    }

    public function test_completing_a_sprint_moves_unfinished_issues_to_target_sprint(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $sprint = Sprint::factory()->active()->create(['project_id' => $project->id, 'created_by' => $admin->id]);
        $target = Sprint::factory()->create(['project_id' => $project->id, 'created_by' => $admin->id]);
        $issue = $this->createIssue($project, $admin->id);
        $issue->sprint_id = $sprint->id;
        $issue->save();

        Sanctum::actingAs($admin);

        $this->postJson("/api/sprints/{$sprint->id}/complete", [
            'carry_over' => 'move_to_sprint',
            'target_sprint_id' => $target->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', SprintStatus::COMPLETED->value);

        $this->assertSame($target->id, $issue->fresh()->sprint_id);
        $this->assertDatabaseHas('issue_sprint_histories', [
            'issue_id' => $issue->id,
            'from_sprint_id' => $sprint->id,
            'to_sprint_id' => $target->id,
        ]);
    }

    public function test_cancelling_a_sprint_returns_issues_to_backlog(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $sprint = Sprint::factory()->active()->create(['project_id' => $project->id, 'created_by' => $admin->id]);
        $issue = $this->createIssue($project, $admin->id);
        $issue->sprint_id = $sprint->id;
        $issue->save();

        Sanctum::actingAs($admin);

        $this->postJson("/api/sprints/{$sprint->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', SprintStatus::CANCELLED->value);

        $this->assertNull($issue->fresh()->sprint_id);
    }

    public function test_adding_issues_to_a_planned_sprint_records_history(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $sprint = Sprint::factory()->create(['project_id' => $project->id, 'created_by' => $admin->id]);
        $issue = $this->createIssue($project, $admin->id);

        Sanctum::actingAs($admin);

        $this->postJson("/api/sprints/{$sprint->id}/issues", [
            'issue_ids' => [$issue->id],
        ])->assertNoContent();

        $this->assertSame($sprint->id, $issue->fresh()->sprint_id);
        $this->assertDatabaseHas('issue_sprint_histories', [
            'issue_id' => $issue->id,
            'to_sprint_id' => $sprint->id,
        ]);
    }

    public function test_issues_assigned_to_another_sprint_cannot_be_added(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $sprintA = Sprint::factory()->create(['project_id' => $project->id, 'created_by' => $admin->id]);
        $sprintB = Sprint::factory()->create(['project_id' => $project->id, 'created_by' => $admin->id]);
        $issue = $this->createIssue($project, $admin->id);
        $issue->sprint_id = $sprintA->id;
        $issue->save();

        Sanctum::actingAs($admin);

        $this->postJson("/api/sprints/{$sprintB->id}/issues", [
            'issue_ids' => [$issue->id],
        ])->assertStatus(422);

        $this->assertSame($sprintA->id, $issue->fresh()->sprint_id);
    }

    public function test_started_sprints_reject_new_issues(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $sprint = Sprint::factory()->active()->create(['project_id' => $project->id, 'created_by' => $admin->id]);
        $issue = $this->createIssue($project, $admin->id);

        Sanctum::actingAs($admin);

        $this->postJson("/api/sprints/{$sprint->id}/issues", [
            'issue_ids' => [$issue->id],
        ])->assertStatus(422);
    }

    public function test_sprint_report_summarises_progress(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $sprint = Sprint::factory()->active()->create(['project_id' => $project->id, 'created_by' => $admin->id]);

        $open = $this->createIssue($project, $admin->id);
        $open->sprint_id = $sprint->id;
        $open->story_points = 3;
        $open->status = 'in_progress';
        $open->save();

        $done = $this->createIssue($project, $admin->id);
        $done->sprint_id = $sprint->id;
        $done->story_points = 5;
        $done->status = 'resolved';
        $done->save();

        Sanctum::actingAs($admin);

        $this->getJson("/api/sprints/{$sprint->id}/report")
            ->assertOk()
            ->assertJsonPath('data.total_issues', 2)
            ->assertJsonPath('data.total_story_points', 8)
            ->assertJsonPath('data.completed_story_points', 5);
    }

    public function test_non_members_cannot_manage_sprints(): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->developer()->create();
        $project = $this->makeProject($admin, [$admin]);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/projects/{$project->id}/sprints")->assertForbidden();
        $this->postJson("/api/projects/{$project->id}/sprints", ['name' => 'Nope'])->assertForbidden();
    }
}
