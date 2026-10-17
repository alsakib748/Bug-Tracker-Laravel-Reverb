<?php

namespace Tests\Feature;

use App\Ai\Agents\BugReportAssistant;
use App\Jobs\AnalyzeBugReportJob;
use App\Models\AiBugReportSuggestion;
use App\Models\Issue;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use App\Services\Ai\BugReportContextBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiBugReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Any prompt that is not explicitly stubbed should fail loudly.
        BugReportAssistant::fake()->preventStrayPrompts();

        $this->admin = User::factory()->admin()->create();
        $this->project = Project::factory()->create(['created_by' => $this->admin->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function suggestionResponse(array $overrides = []): array
    {
        return array_merge([
            'improved_title' => 'Improve login flow',
            'improved_description' => 'A clearer description of the login failure.',
            'suggested_priority' => 'high',
            'priority_reason' => 'Blocks all users.',
            'confidence' => 'medium',
            'missing_information' => [],
            'suggested_labels' => [],
            'questions_for_reporter' => [],
        ], $overrides);
    }

    private function makeLabel(Project $project, string $name = 'Backend'): Label
    {
        return Label::create([
            'project_id' => $project->id,
            'name' => $name,
            'normalized_name' => strtolower($name),
            'color' => '#3490dc',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_authorized_user_can_queue_an_analysis(): void
    {
        Queue::fake();

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/projects/{$this->project->id}/ai/bug-report-analyses", [
            'title' => 'Login broken',
            'description' => 'Login fails after a password reset.',
        ])->assertStatus(202)->assertJsonPath('data.status', 'queued');

        $this->assertDatabaseHas('ai_bug_report_suggestions', [
            'project_id' => $this->project->id,
            'requested_by' => $this->admin->id,
            'status' => 'queued',
        ]);

        Queue::assertPushed(AnalyzeBugReportJob::class);
    }

    public function test_analysis_produces_validated_suggestions(): void
    {
        $label = $this->makeLabel($this->project, 'Backend');

        BugReportAssistant::fake([
            $this->suggestionResponse([
                'improved_title' => 'Login fails after password reset',
                'suggested_priority' => 'high',
                'suggested_labels' => [['label_id' => $label->id, 'reason' => 'Server-side issue']],
            ]),
        ])->preventStrayPrompts();

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/projects/{$this->project->id}/ai/bug-report-analyses", [
            'title' => 'Login broken',
            'description' => 'Login fails after a password reset.',
        ])->assertStatus(202);

        $analysis = AiBugReportSuggestion::firstOrFail();

        $this->assertSame('ready', $analysis->status);
        $this->assertSame('Login fails after password reset', $analysis->suggestions['improved_title']);
        $this->assertSame('high', $analysis->suggestions['suggested_priority']);
        $this->assertSame($label->id, $analysis->suggestions['suggested_labels'][0]['label_id']);
    }

    public function test_validator_drops_unknown_label_ids(): void
    {
        $label = $this->makeLabel($this->project, 'Backend');

        BugReportAssistant::fake([
            $this->suggestionResponse([
                'suggested_labels' => [
                    ['label_id' => $label->id, 'reason' => 'Relevant'],
                    ['label_id' => 999999, 'reason' => 'Does not exist'],
                ],
            ]),
        ])->preventStrayPrompts();

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/projects/{$this->project->id}/ai/bug-report-analyses", [
            'title' => 'Test',
            'description' => 'Test description',
        ])->assertStatus(202);

        $suggestions = AiBugReportSuggestion::firstOrFail()->suggestions;

        $this->assertSame([$label->id], array_column($suggestions['suggested_labels'], 'label_id'));
    }

    public function test_issue_must_belong_to_the_project(): void
    {
        Queue::fake();

        $otherProject = Project::factory()->create(['created_by' => $this->admin->id]);
        $otherIssue = Issue::factory()->create([
            'project_id' => $otherProject->id,
            'reporter_id' => $this->admin->id,
        ]);

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/projects/{$this->project->id}/ai/bug-report-analyses", [
            'title' => 'Test',
            'description' => 'Test description',
            'issue_id' => $otherIssue->id,
        ])->assertStatus(404);
    }

    public function test_outsiders_cannot_request_an_analysis(): void
    {
        $outsider = User::factory()->developer()->create();

        Sanctum::actingAs($outsider);

        $this->postJson("/api/projects/{$this->project->id}/ai/bug-report-analyses", [
            'title' => 'Test',
            'description' => 'Test description',
        ])->assertForbidden();
    }

    public function test_apply_updates_the_issue_and_syncs_labels(): void
    {
        $label = $this->makeLabel($this->project, 'Backend');

        $issue = Issue::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->admin->id,
            'title' => 'Login broken',
            'priority' => 'low',
        ]);

        $analysis = AiBugReportSuggestion::factory()->ready()->create([
            'project_id' => $this->project->id,
            'issue_id' => $issue->id,
            'requested_by' => $this->admin->id,
            'input_hash' => app(BugReportContextBuilder::class)->hashIssue($issue),
            'suggestions' => $this->suggestionResponse([
                'improved_title' => 'Login fails after password reset',
                'suggested_priority' => 'high',
            ]),
        ]);

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/ai/bug-report-analyses/{$analysis->id}/apply", [
            'accepted' => [
                'title' => true,
                'priority' => true,
                'label_ids' => [$label->id],
            ],
        ])->assertOk();

        $issue->refresh();

        $this->assertSame('Login fails after password reset', $issue->title);
        $this->assertSame('high', $issue->priority->value);
        $this->assertTrue($issue->labels()->whereKey($label->id)->exists());

        // The description suggestion was left unapplied, so the analysis stays open.
        $this->assertSame('partially_applied', $analysis->fresh()->status);
    }

    public function test_applying_all_suggestions_marks_the_analysis_applied(): void
    {
        $issue = Issue::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->admin->id,
            'title' => 'Login broken',
        ]);

        $analysis = AiBugReportSuggestion::factory()->ready()->create([
            'project_id' => $this->project->id,
            'issue_id' => $issue->id,
            'requested_by' => $this->admin->id,
            'input_hash' => app(BugReportContextBuilder::class)->hashIssue($issue),
        ]);

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/ai/bug-report-analyses/{$analysis->id}/apply", [
            'accepted' => [
                'title' => true,
                'description' => true,
                'priority' => true,
            ],
        ])->assertOk();

        $this->assertSame('applied', $analysis->fresh()->status);
    }

    public function test_expired_analysis_cannot_be_applied(): void
    {
        $analysis = AiBugReportSuggestion::factory()->ready()->create([
            'project_id' => $this->project->id,
            'requested_by' => $this->admin->id,
            'expires_at' => now()->subHour(),
        ]);

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/ai/bug-report-analyses/{$analysis->id}/apply", [
            'accepted' => ['title' => true],
        ])->assertStatus(422);

        $this->assertSame('expired', $analysis->fresh()->status);
    }

    public function test_show_expires_a_stale_analysis(): void
    {
        $analysis = AiBugReportSuggestion::factory()->ready()->create([
            'project_id' => $this->project->id,
            'requested_by' => $this->admin->id,
            'expires_at' => now()->subHour(),
        ]);

        Sanctum::actingAs($this->admin);

        $this->getJson("/api/ai/bug-report-analyses/{$analysis->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'expired')
            ->assertJsonMissingPath('data.suggestions');
    }

    public function test_prune_command_expires_stale_analyses(): void
    {
        $analysis = AiBugReportSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'requested_by' => $this->admin->id,
            'status' => 'queued',
            'expires_at' => now()->subHour(),
        ]);

        $this->artisan('ai:prune-analyses')->assertSuccessful();

        $this->assertSame('expired', $analysis->fresh()->status);
    }

    public function test_analysis_is_stale_when_the_issue_changed(): void
    {
        $issue = Issue::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->admin->id,
            'title' => 'Original title',
        ]);

        $analysis = AiBugReportSuggestion::factory()->ready()->create([
            'project_id' => $this->project->id,
            'issue_id' => $issue->id,
            'requested_by' => $this->admin->id,
            'input_hash' => app(BugReportContextBuilder::class)->hashIssue($issue),
        ]);

        $issue->update(['title' => 'Changed after analysis']);

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/ai/bug-report-analyses/{$analysis->id}/apply", [
            'accepted' => ['title' => true],
        ])->assertStatus(422);

        $this->assertSame('Changed after analysis', $issue->fresh()->title);
    }

    public function test_apply_rejects_labels_from_another_project(): void
    {
        $otherProject = Project::factory()->create(['created_by' => $this->admin->id]);
        $foreignLabel = $this->makeLabel($otherProject, 'Foreign');

        $issue = Issue::factory()->create([
            'project_id' => $this->project->id,
            'reporter_id' => $this->admin->id,
        ]);

        $analysis = AiBugReportSuggestion::factory()->ready()->create([
            'project_id' => $this->project->id,
            'issue_id' => $issue->id,
            'requested_by' => $this->admin->id,
            'input_hash' => app(BugReportContextBuilder::class)->hashIssue($issue),
        ]);

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/ai/bug-report-analyses/{$analysis->id}/apply", [
            'accepted' => ['label_ids' => [$foreignLabel->id]],
        ])->assertStatus(422);

        $this->assertFalse($issue->fresh()->labels()->whereKey($foreignLabel->id)->exists());
    }

    public function test_issue_can_be_created_with_suggested_labels(): void
    {
        $label = $this->makeLabel($this->project, 'Backend');

        Sanctum::actingAs($this->admin);

        $this->postJson('/api/issues', [
            'project_id' => $this->project->id,
            'title' => 'Login broken',
            'description' => 'Login fails after a password reset.',
            'priority' => 'high',
            'severity' => 'major',
            'type' => 'bug',
            'label_ids' => [$label->id],
        ])->assertOk();

        $issue = Issue::where('title', 'Login broken')->firstOrFail();

        $this->assertTrue($issue->labels()->whereKey($label->id)->exists());
    }

    public function test_analysis_can_be_dismissed(): void
    {
        $analysis = AiBugReportSuggestion::factory()->ready()->create([
            'project_id' => $this->project->id,
            'requested_by' => $this->admin->id,
        ]);

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/ai/bug-report-analyses/{$analysis->id}/dismiss")->assertOk();

        $this->assertSame('dismissed', $analysis->fresh()->status);
    }

    public function test_provider_rate_limit_marks_the_analysis_as_failed(): void
    {
        BugReportAssistant::fake(fn () => throw RateLimitedException::forProvider('gemini'));

        Sanctum::actingAs($this->admin);

        $this->postJson("/api/projects/{$this->project->id}/ai/bug-report-analyses", [
            'title' => 'Login broken',
            'description' => 'Login fails after a password reset.',
        ])->assertStatus(202);

        $analysis = AiBugReportSuggestion::firstOrFail();

        $this->assertSame('failed', $analysis->status);
        $this->assertSame('provider_rate_limited', $analysis->error_code);
    }

    public function test_show_hides_suggestions_until_ready(): void
    {
        $analysis = AiBugReportSuggestion::factory()->create([
            'project_id' => $this->project->id,
            'requested_by' => $this->admin->id,
            'status' => 'processing',
        ]);

        Sanctum::actingAs($this->admin);

        $this->getJson("/api/ai/bug-report-analyses/{$analysis->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonMissingPath('data.suggestions');
    }
}
