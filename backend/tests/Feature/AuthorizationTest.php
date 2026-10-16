<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function addMember(Project $project, User $user, string $role = 'developer'): void
    {
        $project->members()->attach($user->id, [
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    // ---------- Cross-project isolation ----------

    public function test_user_cannot_view_a_project_they_are_not_a_member_of(): void
    {
        $owner = User::factory()->developer()->create();
        $outsider = User::factory()->developer()->create();

        $project = Project::factory()->create(['created_by' => $owner->id]);
        $this->addMember($project, $owner);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/projects/{$project->id}")->assertStatus(403);
    }

    public function test_user_cannot_view_an_issue_in_a_project_they_do_not_belong_to(): void
    {
        $owner = User::factory()->developer()->create();
        $outsider = User::factory()->developer()->create();

        $project = Project::factory()->create(['created_by' => $owner->id]);
        $this->addMember($project, $owner);

        $issue = Issue::factory()->create([
            'project_id' => $project->id,
            'reporter_id' => $owner->id,
        ]);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/issues/{$issue->id}")->assertStatus(403);
    }

    public function test_issue_index_is_scoped_to_the_users_projects(): void
    {
        $user = User::factory()->developer()->create();

        $visibleProject = Project::factory()->create(['created_by' => $user->id]);
        $this->addMember($visibleProject, $user);
        $visibleIssue = Issue::factory()->create([
            'project_id' => $visibleProject->id,
            'reporter_id' => $user->id,
        ]);

        $hiddenProject = Project::factory()->create(['created_by' => $user->id]);
        $hiddenIssue = Issue::factory()->create([
            'project_id' => $hiddenProject->id,
            'reporter_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/issues');
        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($visibleIssue->id));
        $this->assertFalse($ids->contains($hiddenIssue->id));
    }

    // ---------- User management is admin-only ----------

    public function test_non_admin_cannot_list_users(): void
    {
        $developer = User::factory()->developer()->create();

        Sanctum::actingAs($developer);

        $this->getJson('/api/users')->assertStatus(403);
    }

    public function test_non_admin_cannot_create_users(): void
    {
        $developer = User::factory()->developer()->create();

        Sanctum::actingAs($developer);

        $this->postJson('/api/users', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertStatus(403);
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/users')->assertOk();
    }

    // ---------- Comment ownership ----------

    public function test_only_the_comment_owner_or_admin_can_update_or_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->developer()->create();
        $otherMember = User::factory()->developer()->create();

        $project = Project::factory()->create(['created_by' => $admin->id]);
        $this->addMember($project, $author);
        $this->addMember($project, $otherMember);

        $issue = Issue::factory()->create([
            'project_id' => $project->id,
            'reporter_id' => $author->id,
        ]);

        $comment = Comment::factory()->create([
            'issue_id' => $issue->id,
            'user_id' => $author->id,
            'comment' => 'Original',
        ]);

        // Another member cannot edit or delete it.
        Sanctum::actingAs($otherMember);
        $this->putJson("/api/comments/{$comment->id}", ['comment' => 'Hacked'])
            ->assertStatus(403);
        $this->deleteJson("/api/comments/{$comment->id}")->assertStatus(403);

        // The author can edit it.
        Sanctum::actingAs($author);
        $this->putJson("/api/comments/{$comment->id}", ['comment' => 'Edited'])
            ->assertOk()
            ->assertJsonPath('data.comment', 'Edited');

        // An admin can delete it.
        Sanctum::actingAs($admin);
        $this->deleteJson("/api/comments/{$comment->id}")->assertOk();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    // ---------- Project membership management ----------

    public function test_plain_member_cannot_manage_project_members(): void
    {
        $owner = User::factory()->developer()->create();
        $member = User::factory()->developer()->create();
        $candidate = User::factory()->developer()->create();

        $project = Project::factory()->create(['created_by' => $owner->id]);
        $this->addMember($project, $owner);
        $this->addMember($project, $member);

        Sanctum::actingAs($member);

        $this->postJson("/api/projects/{$project->id}/members", [
            'user_id' => $candidate->id,
            'role' => 'developer',
        ])->assertStatus(403);
    }

    public function test_project_creator_can_manage_members(): void
    {
        $owner = User::factory()->developer()->create();
        $candidate = User::factory()->developer()->create();

        $project = Project::factory()->create(['created_by' => $owner->id]);
        $this->addMember($project, $owner);

        Sanctum::actingAs($owner);

        $this->postJson("/api/projects/{$project->id}/members", [
            'user_id' => $candidate->id,
            'role' => 'developer',
        ])->assertStatus(201);

        $this->assertTrue(
            $project->members()->where('users.id', $candidate->id)->exists()
        );
    }
}
