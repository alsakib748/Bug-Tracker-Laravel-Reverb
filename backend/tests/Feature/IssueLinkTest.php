<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\IssueLink;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IssueLinkTest extends TestCase
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

    private function createIssue(Project $project, int $reporterId, string $title = 'Linked issue'): Issue
    {
        return Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $reporterId,
            'title' => $title,
            'description' => 'Link test issue',
            'priority' => 'medium',
            'severity' => 'major',
            'type' => 'bug',
            'status' => 'open',
        ]);
    }

    public function test_issues_in_the_same_project_can_be_linked(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $a = $this->createIssue($project, $admin->id, 'Issue A');
        $b = $this->createIssue($project, $admin->id, 'Issue B');

        Sanctum::actingAs($admin);

        $this->postJson("/api/issues/{$a->id}/links", [
            'target_issue_id' => $b->id,
            'link_type' => 'blocks',
        ])->assertCreated();

        $this->assertDatabaseHas('issue_links', [
            'source_issue_id' => $a->id,
            'target_issue_id' => $b->id,
            'link_type' => 'blocks',
            'created_by' => $admin->id,
        ]);
    }

    public function test_link_direction_labels_depend_on_the_current_issue(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $a = $this->createIssue($project, $admin->id, 'Issue A');
        $b = $this->createIssue($project, $admin->id, 'Issue B');

        IssueLink::create([
            'source_issue_id' => $a->id,
            'target_issue_id' => $b->id,
            'link_type' => 'blocks',
            'created_by' => $admin->id,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/issues/{$a->id}/links")
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Blocks')
            ->assertJsonPath('data.0.direction', 'outgoing')
            ->assertJsonPath('data.0.issue.id', $b->id);

        $this->getJson("/api/issues/{$b->id}/links")
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Blocked by')
            ->assertJsonPath('data.0.direction', 'incoming')
            ->assertJsonPath('data.0.issue.id', $a->id);
    }

    public function test_issue_cannot_be_linked_to_itself(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $a = $this->createIssue($project, $admin->id, 'Issue A');

        Sanctum::actingAs($admin);

        $this->postJson("/api/issues/{$a->id}/links", [
            'target_issue_id' => $a->id,
            'link_type' => 'blocks',
        ])->assertStatus(422);
    }

    public function test_issues_from_different_projects_cannot_be_linked(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $other = $this->makeProject($admin, [$admin]);
        $a = $this->createIssue($project, $admin->id, 'Issue A');
        $b = $this->createIssue($other, $admin->id, 'Issue B');

        Sanctum::actingAs($admin);

        $this->postJson("/api/issues/{$a->id}/links", [
            'target_issue_id' => $b->id,
            'link_type' => 'relates_to',
        ])->assertStatus(422);
    }

    public function test_duplicate_link_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $a = $this->createIssue($project, $admin->id, 'Issue A');
        $b = $this->createIssue($project, $admin->id, 'Issue B');

        Sanctum::actingAs($admin);

        $payload = ['target_issue_id' => $b->id, 'link_type' => 'blocks'];

        $this->postJson("/api/issues/{$a->id}/links", $payload)->assertCreated();
        $this->postJson("/api/issues/{$a->id}/links", $payload)->assertStatus(422);

        $this->assertDatabaseCount('issue_links', 1);
    }

    public function test_symmetric_links_are_normalized_to_a_single_row(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $a = $this->createIssue($project, $admin->id, 'Issue A');
        $b = $this->createIssue($project, $admin->id, 'Issue B');

        Sanctum::actingAs($admin);

        // Link from B -> A, but relates_to is symmetric so it must be stored
        // with the lower id as the source.
        $this->postJson("/api/issues/{$b->id}/links", [
            'target_issue_id' => $a->id,
            'link_type' => 'relates_to',
        ])->assertCreated();

        $this->assertDatabaseCount('issue_links', 1);
        $this->assertDatabaseHas('issue_links', [
            'source_issue_id' => min($a->id, $b->id),
            'target_issue_id' => max($a->id, $b->id),
            'link_type' => 'relates_to',
        ]);

        // The reverse direction is treated as a duplicate.
        $this->postJson("/api/issues/{$a->id}/links", [
            'target_issue_id' => $b->id,
            'link_type' => 'relates_to',
        ])->assertStatus(422);
    }

    public function test_blocks_cycles_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $a = $this->createIssue($project, $admin->id, 'Issue A');
        $b = $this->createIssue($project, $admin->id, 'Issue B');
        $c = $this->createIssue($project, $admin->id, 'Issue C');

        Sanctum::actingAs($admin);

        $this->postJson("/api/issues/{$a->id}/links", [
            'target_issue_id' => $b->id,
            'link_type' => 'blocks',
        ])->assertCreated();

        $this->postJson("/api/issues/{$b->id}/links", [
            'target_issue_id' => $c->id,
            'link_type' => 'blocks',
        ])->assertCreated();

        // A blocks B blocks C, so C blocking A would close a cycle.
        $this->postJson("/api/issues/{$c->id}/links", [
            'target_issue_id' => $a->id,
            'link_type' => 'blocks',
        ])->assertStatus(422);

        $this->assertDatabaseCount('issue_links', 2);
    }

    public function test_link_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $a = $this->createIssue($project, $admin->id, 'Issue A');
        $b = $this->createIssue($project, $admin->id, 'Issue B');

        $link = IssueLink::create([
            'source_issue_id' => $a->id,
            'target_issue_id' => $b->id,
            'link_type' => 'blocks',
            'created_by' => $admin->id,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/issue-links/{$link->id}")->assertNoContent();

        $this->assertDatabaseMissing('issue_links', ['id' => $link->id]);
    }

    public function test_non_member_cannot_create_links(): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->developer()->create();
        $project = $this->makeProject($admin, [$admin]);
        $a = $this->createIssue($project, $admin->id, 'Issue A');
        $b = $this->createIssue($project, $admin->id, 'Issue B');

        Sanctum::actingAs($outsider);

        $this->postJson("/api/issues/{$a->id}/links", [
            'target_issue_id' => $b->id,
            'link_type' => 'blocks',
        ])->assertStatus(403);
    }
}
