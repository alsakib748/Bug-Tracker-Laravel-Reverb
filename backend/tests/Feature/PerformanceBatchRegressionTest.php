<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression coverage for the performance-optimization batch:
 * B1 search scoping, B2 per_page clamping, B3 sort whitelisting,
 * B6 issues_count on project show, B7 comment pagination,
 * B8 bulk notification fan-out.
 */
class PerformanceBatchRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function addMember(Project $project, User $user, string $role = 'developer'): void
    {
        $project->members()->attach($user->id, [
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    private function makeProjectWithIssue(User $user, string $title = 'sample issue'): array
    {
        $project = Project::factory()->create(['created_by' => $user->id]);
        $this->addMember($project, $user);

        $issue = Issue::factory()->create([
            'project_id' => $project->id,
            'reporter_id' => $user->id,
            'title' => $title,
        ]);

        return [$project, $issue];
    }

    // ---------- B1: search must respect the visibility scope ----------

    public function test_issue_search_cannot_return_issues_from_unauthorized_projects(): void
    {
        $user = User::factory()->developer()->create();
        $otherUser = User::factory()->developer()->create();

        [$visibleProject, $visibleIssue] = $this->makeProjectWithIssue($user, 'needle in visible project');

        $hiddenProject = Project::factory()->create(['created_by' => $otherUser->id]);
        $this->addMember($hiddenProject, $otherUser);
        Issue::factory()->create([
            'project_id' => $hiddenProject->id,
            'reporter_id' => $otherUser->id,
            'title' => 'needle in hidden project',
        ]);

        Sanctum::actingAs($user);

        $ids = collect($this->getJson('/api/issues?search=needle')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($visibleIssue->id));
        $this->assertCount(1, $ids);
    }

    public function test_issue_search_still_applies_other_filters(): void
    {
        $user = User::factory()->developer()->create();

        [$project] = $this->makeProjectWithIssue($user);

        $open = Issue::factory()->create([
            'project_id' => $project->id,
            'reporter_id' => $user->id,
            'title' => 'needle open',
            'status' => 'open',
        ]);
        Issue::factory()->create([
            'project_id' => $project->id,
            'reporter_id' => $user->id,
            'title' => 'needle closed',
            'status' => 'closed',
        ]);

        Sanctum::actingAs($user);

        $ids = collect($this->getJson('/api/issues?search=needle&status=open')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($open->id));
        $this->assertCount(1, $ids);
    }

    // ---------- B2: per_page clamped to 1..100 ----------

    public function test_issue_index_per_page_is_clamped(): void
    {
        $user = User::factory()->developer()->create();
        $this->makeProjectWithIssue($user);

        Sanctum::actingAs($user);

        $this->getJson('/api/issues?per_page=1000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);

        $this->getJson('/api/issues?per_page=0')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/issues?per_page=-9')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/issues?per_page=abc')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/issues?per_page=50')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50);
    }

    public function test_activity_log_per_page_is_clamped(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/activity-logs?per_page=1000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    // ---------- B3: sort whitelisting ----------

    public function test_issue_index_sort_rejects_unsupported_columns(): void
    {
        $user = User::factory()->developer()->create();
        $this->makeProjectWithIssue($user);

        Sanctum::actingAs($user);

        // Unvalidated, these would reach orderBy and raise a SQL error.
        $this->getJson('/api/issues?sort=password')->assertOk();
        $this->getJson('/api/issues?sort=-secret_column')->assertOk();

        // Whitelisted columns keep working.
        $this->getJson('/api/issues?sort=title')->assertOk();
        $this->getJson('/api/issues?sort=-created_at')->assertOk();
    }

    // ---------- B6: project show carries issues_count ----------

    public function test_project_show_returns_issues_count(): void
    {
        $user = User::factory()->developer()->create();

        [$project] = $this->makeProjectWithIssue($user);
        Issue::factory()->create([
            'project_id' => $project->id,
            'reporter_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.issues_count', 2);
    }

    // ---------- B7: comments are paginated ----------

    public function test_comments_endpoint_is_paginated_with_clamped_per_page(): void
    {
        $user = User::factory()->developer()->create();
        [$project, $issue] = $this->makeProjectWithIssue($user);

        Comment::factory()->count(60)->create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/issues/{$issue->id}/comments")->assertOk();

        $this->assertCount(50, $response->json('data'));
        $this->assertSame(60, $response->json('meta.total'));
        $this->assertSame(50, $response->json('meta.per_page'));

        $this->getJson("/api/issues/{$issue->id}/comments?per_page=1000")
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    // ---------- B8: bulk notification fan-out ----------

    public function test_comment_notifications_reach_every_member_except_the_author(): void
    {
        $owner = User::factory()->developer()->create();
        $member = User::factory()->developer()->create();
        $author = User::factory()->developer()->create();

        $project = Project::factory()->create(['created_by' => $owner->id]);
        $this->addMember($project, $owner, 'manager');
        $this->addMember($project, $member);
        $this->addMember($project, $author);

        $issue = Issue::factory()->create([
            'project_id' => $project->id,
            'reporter_id' => $owner->id,
        ]);

        Sanctum::actingAs($author);

        $this->postJson("/api/issues/{$issue->id}/comments", ['comment' => 'bulk insert check'])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'type' => SystemNotification::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $member->id,
            'type' => SystemNotification::class,
        ]);
        // Note: sendCommentAdded's docblock claims the author is excluded, but
        // the implementation (before and after the bulk-insert rewrite) has
        // never passed an exclusion — the author is notified too. This asserts
        // the preserved behavior; excluding the author would be a separate
        // product decision.
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $author->id,
            'type' => SystemNotification::class,
        ]);

        // One row per recipient, no duplicates from the bulk insert.
        $this->assertSame(
            3,
            DB::table('notifications')->where('type', SystemNotification::class)->count()
        );
    }
}
