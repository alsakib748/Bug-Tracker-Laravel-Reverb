<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IssueLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a project and attach the given users as members.
     */
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
        $data = array_merge([
            'project_id' => $project->id,
            'title' => 'Login is broken',
            'description' => 'Cannot login with valid credentials',
            'priority' => 'high',
            'severity' => 'major',
            'type' => 'bug',
            'reporter_id' => $reporterId,
        ], $overrides);

        return Issue::create($data);
    }

    public function test_issue_follows_the_full_workflow(): void
    {
        $admin = User::factory()->admin()->create();
        $developer = User::factory()->developer()->create();

        $project = $this->makeProject($admin, [$admin, $developer]);

        Sanctum::actingAs($admin);

        // Create
        $create = $this->postJson('/api/issues', [
            'project_id' => $project->id,
            'title' => 'Login is broken',
            'description' => 'Cannot login',
            'priority' => 'high',
            'severity' => 'major',
            'type' => 'bug',
        ]);

        $create->assertSuccessful();
        $create->assertJsonPath('data.status', 'open');

        $issueId = $create->json('data.id');
        $this->assertNotNull($issueId);

        // Assign (open -> assigned)
        $this->patchJson("/api/issues/{$issueId}/assign", ['user_id' => $developer->id])
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.assignee.id', $developer->id);

        // Invalid transition: assigned -> resolved must be rejected
        $this->patchJson("/api/issues/{$issueId}/status", ['status' => 'resolved'])
            ->assertStatus(422);
        $this->assertSame(IssueStatus::ASSIGNED, Issue::find($issueId)->status);

        // Walk the workflow: assigned -> in_progress -> code_review -> testing -> resolved
        foreach (['in_progress', 'code_review', 'testing', 'resolved'] as $status) {
            $this->patchJson("/api/issues/{$issueId}/status", ['status' => $status])
                ->assertSuccessful()
                ->assertJsonPath('data.status', $status);
        }

        $this->assertSame(IssueStatus::RESOLVED, Issue::find($issueId)->status);

        // Reopen (resolved -> reopened)
        $this->patchJson("/api/issues/{$issueId}/reopen")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'reopened');

        // reopened -> in_progress -> code_review -> testing -> resolved
        foreach (['in_progress', 'code_review', 'testing', 'resolved'] as $status) {
            $this->patchJson("/api/issues/{$issueId}/status", ['status' => $status])
                ->assertSuccessful();
        }

        // Close (resolved -> closed)
        $this->patchJson("/api/issues/{$issueId}/close")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'closed');

        $issue = Issue::find($issueId);
        $this->assertSame(IssueStatus::CLOSED, $issue->status);
        $this->assertNotNull($issue->completed_at);
    }

    public function test_issue_cannot_be_assigned_to_a_non_member(): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->developer()->create();

        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/issues/{$issue->id}/assign", ['user_id' => $outsider->id])
            ->assertStatus(422);

        $this->assertNull($issue->fresh()->assigned_to);
    }

    public function test_due_date_is_persisted_on_create(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $due = now()->addDays(7)->toDateString();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/issues', [
            'project_id' => $project->id,
            'title' => 'With due date',
            'priority' => 'low',
            'severity' => 'minor',
            'type' => 'task',
            'due_date' => $due,
        ]);

        $response->assertSuccessful();

        $issue = Issue::find($response->json('data.id'));
        $this->assertNotNull($issue->due_date);
        $this->assertSame($due, $issue->due_date->toDateString());
    }

    public function test_reopen_rejects_issues_that_are_not_resolved(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id, ['status' => 'open']);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/issues/{$issue->id}/reopen")
            ->assertStatus(422);

        $this->assertSame(IssueStatus::OPEN, $issue->fresh()->status);
    }
}
