<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WatcherTest extends TestCase
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

    private function createIssue(Project $project, int $reporterId): Issue
    {
        return Issue::create([
            'project_id' => $project->id,
            'reporter_id' => $reporterId,
            'title' => 'Watcher issue',
            'description' => 'Watcher test issue',
            'priority' => 'medium',
            'severity' => 'major',
            'type' => 'bug',
            'status' => 'open',
        ]);
    }

    public function test_user_can_watch_and_unwatch_an_issue(): void
    {
        $admin = User::factory()->admin()->create();
        $developer = User::factory()->developer()->create();
        $project = $this->makeProject($admin, [$admin, $developer]);
        $issue = $this->createIssue($project, $admin->id);

        Sanctum::actingAs($developer);

        $this->getJson("/api/issues/{$issue->id}/watchers/me")
            ->assertOk()
            ->assertJson(['watching' => false, 'count' => 0]);

        $this->postJson("/api/issues/{$issue->id}/watchers")
            ->assertOk()
            ->assertJson(['watching' => true, 'count' => 1]);

        $this->assertDatabaseHas('issue_watchers', [
            'issue_id' => $issue->id,
            'user_id' => $developer->id,
        ]);

        // Watching twice is idempotent.
        $this->postJson("/api/issues/{$issue->id}/watchers")->assertOk();
        $this->assertDatabaseCount('issue_watchers', 1);

        $this->deleteJson("/api/issues/{$issue->id}/watchers/me")
            ->assertOk()
            ->assertJson(['watching' => false, 'count' => 0]);

        $this->assertDatabaseMissing('issue_watchers', [
            'issue_id' => $issue->id,
            'user_id' => $developer->id,
        ]);
    }

    public function test_watchers_endpoint_lists_watchers(): void
    {
        $admin = User::factory()->admin()->create();
        $developer = User::factory()->developer()->create();
        $project = $this->makeProject($admin, [$admin, $developer]);
        $issue = $this->createIssue($project, $admin->id);

        $issue->watchers()->attach($developer->id, ['created_at' => now()]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/issues/{$issue->id}/watchers")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $developer->id);

        $this->getJson("/api/issues/{$issue->id}/watchers/me")
            ->assertOk()
            ->assertJsonPath('watching', false)
            ->assertJsonPath('count', 1);
    }

    public function test_non_member_cannot_watch_or_list_watchers(): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->developer()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id);

        Sanctum::actingAs($outsider);

        $this->postJson("/api/issues/{$issue->id}/watchers")->assertStatus(403);
        $this->getJson("/api/issues/{$issue->id}/watchers")->assertStatus(403);
    }
}
