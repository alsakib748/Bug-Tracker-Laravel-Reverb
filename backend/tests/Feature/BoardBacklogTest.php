<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BoardBacklogTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(User $creator, array $members): Project
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
            'title' => 'Board issue',
            'description' => 'Board test issue',
            'priority' => 'medium',
            'severity' => 'major',
            'type' => 'bug',
            'status' => 'open',
        ], $attributes));
    }

    public function test_board_returns_assigned_issues_with_lean_assignee_payload(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $assignee = User::factory()->create();
        $issue = $this->createIssue($project, $admin->id, ['status' => 'in_progress']);
        $issue->assigned_to = $assignee->id;
        $issue->save();

        Sanctum::actingAs($admin);

        $this->getJson("/api/projects/{$project->id}/board")
            ->assertOk()
            ->assertJsonPath('data.columns.2.status', 'in_progress')
            ->assertJsonPath('data.columns.2.issues.0.assignee.id', $assignee->id)
            ->assertJsonPath('data.columns.2.issues.0.assignee.name', $assignee->name);
    }

    public function test_board_groups_issues_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);

        $this->createIssue($project, $admin->id, ['status' => 'open', 'board_position' => 1]);
        $this->createIssue($project, $admin->id, ['status' => 'in_progress', 'board_position' => 2]);
        $this->createIssue($project, $admin->id, ['status' => 'in_progress', 'board_position' => 1]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/projects/{$project->id}/board")->assertOk();

        $columns = collect($response->json('data.columns'))->keyBy('status');

        $this->assertCount(1, $columns['open']['issues']);
        $this->assertCount(2, $columns['in_progress']['issues']);
        // Lowest board_position first.
        $this->assertSame(1, $columns['in_progress']['issues'][0]['board_position']);
        $this->assertSame(2, $columns['in_progress']['issues'][1]['board_position']);
    }

    public function test_moving_an_issue_across_columns_records_status_history(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id, ['status' => 'open']);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/issues/{$issue->id}/board-position", [
            'status' => 'in_progress',
            'position' => 0,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $fresh = $issue->fresh();
        $this->assertSame('in_progress', $fresh->status->value);
        $this->assertSame(1, $fresh->version);
        $this->assertDatabaseHas('issue_status_histories', [
            'issue_id' => $issue->id,
            'from_status' => 'open',
            'to_status' => 'in_progress',
        ]);
    }

    public function test_same_column_reorder_does_not_trigger_a_transition(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id, ['status' => 'in_progress', 'board_position' => 0]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/issues/{$issue->id}/board-position", [
            'status' => 'in_progress',
            'position' => 5,
        ])->assertOk();

        $fresh = $issue->fresh();
        $this->assertSame(5, $fresh->board_position);
        $this->assertSame('in_progress', $fresh->status->value);
        $this->assertDatabaseCount('issue_status_histories', 0);
    }

    public function test_backlog_only_lists_unsprinted_actionable_issues(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);

        $inBacklog = $this->createIssue($project, $admin->id);
        $this->createIssue($project, $admin->id, ['status' => 'closed']);
        $sprint = Sprint::factory()->create(['project_id' => $project->id, 'created_by' => $admin->id]);
        $this->createIssue($project, $admin->id, ['sprint_id' => $sprint->id]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/projects/{$project->id}/backlog")->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$inBacklog->id], $ids);
    }

    public function test_backlog_positions_can_be_reordered(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/issues/{$issue->id}/backlog-position", [
            'position' => 4,
        ])->assertOk();

        $this->assertSame(4, $issue->fresh()->backlog_position);
    }

    public function test_non_members_cannot_view_the_board_or_backlog(): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->create(['role' => 'developer']);
        $project = $this->makeProject($admin, [$admin]);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/projects/{$project->id}/board")->assertForbidden();
        $this->getJson("/api/projects/{$project->id}/backlog")->assertForbidden();
    }
}
