<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttachmentSecurityTest extends TestCase
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

    public function test_guest_cannot_upload(): void
    {
        $owner = User::factory()->admin()->create();
        $issue = $this->makeIssue($owner);

        Storage::fake('public');

        $this->post("/api/issues/{$issue->id}/attachments", [
            'file' => UploadedFile::fake()->image('screenshot.png'),
        ], ['Accept' => 'application/json'])->assertStatus(401);
    }

    public function test_non_member_cannot_upload_or_download_attachments(): void
    {
        $owner = User::factory()->admin()->create();
        $issue = $this->makeIssue($owner);

        $attachment = Attachment::factory()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
        ]);

        $outsider = User::factory()->developer()->create();
        Sanctum::actingAs($outsider);

        $this->post("/api/issues/{$issue->id}/attachments", [
            'file' => UploadedFile::fake()->image('intruder.png'),
        ], ['Accept' => 'application/json'])->assertStatus(403);

        $this->getJson("/api/attachments/{$attachment->id}/download")->assertStatus(403);
    }

    public function test_member_can_upload_and_download_own_attachment(): void
    {
        $owner = User::factory()->admin()->create();
        $issue = $this->makeIssue($owner);

        Storage::fake('public');
        Sanctum::actingAs($owner);

        $response = $this->post("/api/issues/{$issue->id}/attachments", [
            'file' => UploadedFile::fake()->image('screenshot.png'),
        ], ['Accept' => 'application/json']);

        $response->assertSuccessful();

        $attachmentId = $response->json('data.id');
        $this->assertDatabaseHas('attachments', [
            'id' => $attachmentId,
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
        ]);

        // Download streams from the real public disk path.
        $path = "issues/{$issue->id}/downloadable.png";
        $fullPath = storage_path('app/public/' . $path);

        File::ensureDirectoryExists(dirname($fullPath));
        File::put($fullPath, 'payload');

        $stored = Attachment::factory()->create([
            'issue_id' => $issue->id,
            'user_id' => $owner->id,
            'file_path' => $path,
        ]);

        $this->getJson("/api/attachments/{$stored->id}/download")->assertOk();

        File::delete($fullPath);
    }

    public function test_oversized_file_is_rejected(): void
    {
        $owner = User::factory()->admin()->create();
        $issue = $this->makeIssue($owner);

        Storage::fake('public');
        Sanctum::actingAs($owner);

        // 30 MB exceeds the 20 MB cap.
        $this->post("/api/issues/{$issue->id}/attachments", [
            'file' => UploadedFile::fake()->create('dump.zip', 30000),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_disallowed_file_extension_is_rejected(): void
    {
        $owner = User::factory()->admin()->create();
        $issue = $this->makeIssue($owner);

        Storage::fake('public');
        Sanctum::actingAs($owner);

        $this->post("/api/issues/{$issue->id}/attachments", [
            'file' => UploadedFile::fake()->create('malware.exe', 10),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->assertDatabaseCount('attachments', 0);
    }
}
