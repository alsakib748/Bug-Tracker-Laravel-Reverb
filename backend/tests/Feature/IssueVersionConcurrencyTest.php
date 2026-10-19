<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IssueVersionConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private function makeIssue(User $actor): Issue
    {
        $project = Project::factory()->create(['created_by' => $actor->id]);
        $project->members()->attach($actor->id, [
            'role' => 'developer',
            'joined_at' => now(),
        ]);

        return Issue::factory()->create([
            'project_id' => $project->id,
            'reporter_id' => $actor->id,
        ]);
    }

    public function test_stale_version_is_rejected_and_fresh_version_succeeds(): void
    {
        $admin = User::factory()->admin()->create();
        $issue = $this->makeIssue($admin);

        Sanctum::actingAs($admin);

        $initialVersion = $issue->fresh()->version;

        $first = $this->putJson("/api/issues/{$issue->id}", [
            'title' => 'Version one',
            'version' => $initialVersion,
        ]);

        $first->assertOk()->assertJsonPath('data.version', $initialVersion + 1);

        // A client holding the old version must be rejected.
        $stale = $this->putJson("/api/issues/{$issue->id}", [
            'title' => 'Version two',
            'version' => $initialVersion,
        ]);

        $stale->assertStatus(422);
        $this->assertDatabaseHas('issues', ['id' => $issue->id, 'title' => 'Version one']);

        // Retrying with the current version succeeds.
        $retry = $this->putJson("/api/issues/{$issue->id}", [
            'title' => 'Version two',
            'version' => $first->json('data.version'),
        ]);

        $retry->assertOk()->assertJsonPath('data.version', $initialVersion + 2);

        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'title' => 'Version two',
            'version' => $initialVersion + 2,
        ]);
    }

    public function test_update_without_version_is_allowed_for_backwards_compatibility(): void
    {
        $admin = User::factory()->admin()->create();
        $issue = $this->makeIssue($admin);

        Sanctum::actingAs($admin);

        $this->putJson("/api/issues/{$issue->id}", ['title' => 'No version sent'])
            ->assertOk()
            ->assertJsonPath('data.title', 'No version sent');
    }
}
