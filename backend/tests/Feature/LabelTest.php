<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LabelTest extends TestCase
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
            'title' => 'Label issue',
            'description' => 'Label test issue',
            'priority' => 'medium',
            'severity' => 'major',
            'type' => 'bug',
            'status' => 'open',
        ]);
    }

    public function test_manager_can_create_and_list_labels(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/projects/{$project->id}/labels", [
            'name' => 'Bug',
            'color' => '#ff0000',
            'description' => 'A defect',
        ])->assertCreated();

        $this->assertDatabaseHas('labels', [
            'project_id' => $project->id,
            'name' => 'Bug',
            'normalized_name' => 'bug',
            'color' => '#ff0000',
        ]);

        $this->getJson("/api/projects/{$project->id}/labels")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_duplicate_label_name_is_rejected_case_insensitively(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/projects/{$project->id}/labels", ['name' => 'Bug'])
            ->assertCreated();

        $this->postJson("/api/projects/{$project->id}/labels", ['name' => 'bug'])
            ->assertStatus(422);

        $this->assertDatabaseCount('labels', 1);
    }

    public function test_manager_can_update_and_delete_label(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $label = Label::create([
            'project_id' => $project->id,
            'name' => 'Bug',
            'normalized_name' => 'bug',
            'color' => '#64748b',
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/labels/{$label->id}", ['name' => 'Defect', 'color' => '#00ff00'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Defect');

        $this->assertDatabaseHas('labels', [
            'id' => $label->id,
            'name' => 'Defect',
            'normalized_name' => 'defect',
        ]);

        $this->deleteJson("/api/labels/{$label->id}")->assertNoContent();
        $this->assertDatabaseMissing('labels', ['id' => $label->id]);
    }

    public function test_non_manager_member_cannot_create_labels(): void
    {
        $admin = User::factory()->admin()->create();
        $developer = User::factory()->developer()->create();
        $project = $this->makeProject($admin, [$admin, $developer]);

        Sanctum::actingAs($developer);

        $this->postJson("/api/projects/{$project->id}/labels", ['name' => 'Bug'])
            ->assertStatus(403);
    }

    public function test_non_member_cannot_view_labels(): void
    {
        $admin = User::factory()->admin()->create();
        $outsider = User::factory()->developer()->create();
        $project = $this->makeProject($admin, [$admin]);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/projects/{$project->id}/labels")->assertStatus(403);
    }

    public function test_labels_can_be_synced_attached_and_detached_on_an_issue(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id);

        $bug = Label::create([
            'project_id' => $project->id,
            'name' => 'Bug',
            'normalized_name' => 'bug',
            'created_by' => $admin->id,
        ]);
        $urgent = Label::create([
            'project_id' => $project->id,
            'name' => 'Urgent',
            'normalized_name' => 'urgent',
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/issues/{$issue->id}/labels", [
            'label_ids' => [$bug->id],
        ])->assertOk()->assertJsonCount(1, 'data');

        $this->assertDatabaseHas('issue_label', [
            'issue_id' => $issue->id,
            'label_id' => $bug->id,
            'created_by' => $admin->id,
        ]);

        $this->postJson("/api/issues/{$issue->id}/labels", [
            'label_ids' => [$urgent->id],
        ])->assertOk()->assertJsonCount(2, 'data');

        $this->deleteJson("/api/issues/{$issue->id}/labels/{$bug->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertDatabaseMissing('issue_label', [
            'issue_id' => $issue->id,
            'label_id' => $bug->id,
        ]);
    }

    public function test_label_from_another_project_cannot_be_attached(): void
    {
        $admin = User::factory()->admin()->create();
        $project = $this->makeProject($admin, [$admin]);
        $otherProject = $this->makeProject($admin, [$admin]);
        $issue = $this->createIssue($project, $admin->id);

        $foreign = Label::create([
            'project_id' => $otherProject->id,
            'name' => 'Foreign',
            'normalized_name' => 'foreign',
            'created_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/issues/{$issue->id}/labels", [
            'label_ids' => [$foreign->id],
        ])->assertStatus(422);
    }
}
