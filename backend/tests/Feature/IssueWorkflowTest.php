<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IssueWorkflowTest extends TestCase
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

    private function createIssue(Project $project, int $reporterId, array $overrides = []): Issue
    {
        return Issue::create(array_merge([
            'project_id' => $project->id,
            'reporter_id' => $reporterId,
            'title' => 'Workflow issue',
            'description' => 'Workflow test issue',
            'priority' => 'medium',
            'severity' => 'major',
            'type' => 'bug',
            'status' => 'open',
        ], $overrides));
    }

    public function test_valid_transition_records_history(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id, ['status' => 'in_progress']);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/issues/{$issue->id}/transitions", [
            'to_status' => 'code_review',
            'reason' => 'Ready for review',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'code_review');

        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'status' => 'code_review',
        ]);

        $this->assertDatabaseHas('issue_status_histories', [
            'issue_id' => $issue->id,
            'from_status' => 'in_progress',
            'to_status' => 'code_review',
            'changed_by' => $admin->id,
            'reason' => 'Ready for review',
        ]);
    }

    public function test_invalid_transition_is_rejected_and_records_nothing(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id, ['status' => 'open']);

        Sanctum::actingAs($admin);

        $this->postJson("/api/issues/{$issue->id}/transitions", [
            'to_status' => 'closed',
        ])->assertStatus(422);

        $this->assertSame('open', Issue::find($issue->id)->status->value);
        $this->assertDatabaseCount('issue_status_histories', 0);
    }

    public function test_same_status_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id, ['status' => 'open']);

        Sanctum::actingAs($admin);

        $this->postJson("/api/issues/{$issue->id}/transitions", [
            'to_status' => 'open',
        ])->assertStatus(422);
    }

    public function test_history_endpoint_returns_paginated_entries(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id, ['status' => 'open']);

        $issue->statusHistories()->create([
            'from_status' => null,
            'to_status' => 'open',
            'changed_by' => $admin->id,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/issues/{$issue->id}/status-history")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'from_status', 'to_status', 'created_at'],
                ],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_allowed_transitions_endpoint_reflects_current_status(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id, ['status' => 'assigned']);

        Sanctum::actingAs($admin);

        $this->getJson("/api/issues/{$issue->id}/allowed-transitions")
            ->assertOk()
            ->assertJsonPath('current', 'assigned')
            ->assertJsonPath('allowed', ['in_progress', 'open']);
    }

    public function test_initial_status_is_recorded_when_issue_is_created(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/issues', [
            'project_id' => $project->id,
            'title' => 'Brand new issue',
            'priority' => 'high',
            'severity' => 'major',
            'type' => 'bug',
        ]);

        $response->assertSuccessful();

        $issueId = $response->json('data.id');

        $this->assertDatabaseHas('issue_status_histories', [
            'issue_id' => $issueId,
            'from_status' => null,
            'to_status' => 'open',
            'changed_by' => $admin->id,
        ]);
    }

    public function test_resolving_and_closing_sets_lifecycle_timestamps(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id, [
            'status' => 'testing',
            'assigned_to' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/issues/{$issue->id}/transitions", ['to_status' => 'resolved'])
            ->assertOk();

        $resolved = Issue::find($issue->id);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertSame('resolved', $resolved->status->value);

        $this->postJson("/api/issues/{$issue->id}/transitions", ['to_status' => 'closed'])
            ->assertOk();

        $closed = Issue::find($issue->id);
        $this->assertNotNull($closed->closed_at);
        $this->assertSame('closed', $closed->status->value);
    }

    public function test_non_member_cannot_transition_the_issue(): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->developer()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id, ['status' => 'open']);

        Sanctum::actingAs($outsider);

        $this->postJson("/api/issues/{$issue->id}/transitions", [
            'to_status' => 'in_progress',
        ])->assertStatus(403);
    }
}
