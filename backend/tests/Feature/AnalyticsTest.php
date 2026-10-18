<?php

namespace Tests\Feature;

use App\Jobs\GenerateProjectInsights;
use App\Models\Issue;
use App\Models\Project;
use App\Models\ProjectInsight;
use App\Models\User;
use App\Services\Insights\InsightGenerator;
use App\Services\Insights\InsightValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->project = Project::factory()->create(['created_by' => $this->admin->id]);

        $this->project->members()->syncWithoutDetaching([
            $this->admin->id => ['role' => 'manager', 'joined_at' => now()],
        ]);

        Sanctum::actingAs($this->admin);
    }

    private function issue(array $attributes = []): Issue
    {
        return Issue::factory()->create(array_merge([
            'project_id' => $this->project->id,
            'reporter_id' => $this->admin->id,
        ], $attributes));
    }

    public function test_overview_returns_summary(): void
    {
        $this->issue(['status' => 'open']);
        $this->issue(['status' => 'open']);
        $this->issue(['status' => 'open']);

        $this->issue(['status' => 'closed'])->forceFill([
            'resolved_at' => now()->subDay(),
        ])->save();

        $this->issue(['status' => 'closed'])->forceFill([
            'resolved_at' => now()->subDay(),
        ])->save();

        $this->getJson("/api/projects/{$this->project->id}/analytics/overview")
            ->assertOk()
            ->assertJsonPath('summary.open_issues', 3)
            ->assertJsonPath('summary.total_issues', 5)
            ->assertJsonPath('summary.resolved_in_period', 2);
    }

    public function test_status_distribution_groups_correctly(): void
    {
        $this->issue(['status' => 'open']);
        $this->issue(['status' => 'open']);
        $this->issue(['status' => 'in_progress']);

        $response = $this->getJson("/api/projects/{$this->project->id}/analytics/overview")
            ->assertOk()
            ->json('status_distribution');

        $distribution = collect($response)->pluck('count', 'status');

        $this->assertSame(2, $distribution['open']);
        $this->assertSame(1, $distribution['in_progress']);
    }

    public function test_median_resolution_time_is_reported_with_coverage(): void
    {
        $resolved = $this->issue(['status' => 'resolved']);
        $resolved->created_at = now()->subHours(10);
        $resolved->resolved_at = now();
        $resolved->save();

        $this->getJson("/api/projects/{$this->project->id}/analytics/overview")
            ->assertOk()
            ->assertJsonPath('resolution.median_hours', 10)
            ->assertJsonPath('resolution.sample_size', 1)
            ->assertJsonPath('resolution.coverage', 'complete');
    }

    public function test_trends_return_bucketed_series(): void
    {
        $this->issue(['status' => 'open']);

        $resolved = $this->issue(['status' => 'resolved']);
        $resolved->resolved_at = now();
        $resolved->save();

        $this->getJson("/api/projects/{$this->project->id}/analytics/trends?granularity=week")
            ->assertOk()
            ->assertJsonPath('granularity', 'week')
            ->assertJsonStructure(['series' => ['buckets', 'created', 'resolved']]);

        $series = $this->getJson("/api/projects/{$this->project->id}/analytics/trends?granularity=week")
            ->json('series');

        $this->assertSame(2, array_sum($series['created']));
        $this->assertSame(1, array_sum($series['resolved']));
    }

    public function test_workflow_reports_time_in_status_and_reopenings(): void
    {
        $issue = $this->issue(['status' => 'resolved']);

        $base = now()->subDays(2);

        $issue->statusHistories()->create([
            'from_status' => 'open',
            'to_status' => 'assigned',
            'changed_by' => $this->admin->id,
            'created_at' => $base,
        ]);

        $issue->statusHistories()->create([
            'from_status' => 'assigned',
            'to_status' => 'in_progress',
            'changed_by' => $this->admin->id,
            'created_at' => $base->copy()->addHours(2),
        ]);

        $issue->statusHistories()->create([
            'from_status' => 'in_progress',
            'to_status' => 'resolved',
            'changed_by' => $this->admin->id,
            'created_at' => $base->copy()->addHours(5),
        ]);

        $issue->statusHistories()->create([
            'from_status' => 'resolved',
            'to_status' => 'reopened',
            'changed_by' => $this->admin->id,
            'created_at' => $base->copy()->addHours(6),
        ]);

        $response = $this->getJson("/api/projects/{$this->project->id}/analytics/workflow")
            ->assertOk();

        $response->assertJsonPath('reopened_count', 1);

        $timeInStatus = collect($response->json('time_in_status'))->keyBy('status');

        $this->assertSame(2, $timeInStatus['assigned']['median_hours']);
        $this->assertSame(3, $timeInStatus['in_progress']['median_hours']);
    }

    public function test_store_insight_queues_generation(): void
    {
        Queue::fake();

        $response = $this->postJson("/api/projects/{$this->project->id}/insights", [
            'insight_type' => 'project_summary',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.insight_type', 'project_summary');

        Queue::assertPushed(GenerateProjectInsights::class);

        $this->assertDatabaseHas('project_insights', [
            'project_id' => $this->project->id,
            'requested_by' => $this->admin->id,
            'insight_type' => 'project_summary',
            'status' => 'queued',
        ]);
    }

    public function test_store_validates_insight_type(): void
    {
        Queue::fake();

        $this->postJson("/api/projects/{$this->project->id}/insights", [
            'insight_type' => 'not_a_type',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('insight_type');

        Queue::assertNothingPushed();
    }

    public function test_insights_can_be_listed_and_shown(): void
    {
        $insight = ProjectInsight::factory()->create([
            'project_id' => $this->project->id,
            'requested_by' => $this->admin->id,
        ]);

        $this->getJson("/api/projects/{$this->project->id}/insights")
            ->assertOk()
            ->assertJsonPath('data.0.id', $insight->id);

        $this->getJson("/api/insights/{$insight->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $insight->id)
            ->assertJsonPath('data.status', 'queued');
    }

    public function test_non_member_cannot_view_analytics(): void
    {
        $outsider = User::factory()->developer()->create();
        Sanctum::actingAs($outsider);

        $this->getJson("/api/projects/{$this->project->id}/analytics/overview")
            ->assertForbidden();

        $this->postJson("/api/projects/{$this->project->id}/insights", [
            'insight_type' => 'project_summary',
        ])->assertForbidden();
    }

    public function test_job_persists_validated_insight_and_snapshots_metrics(): void
    {
        $this->issue(['status' => 'open']);

        $insight = ProjectInsight::factory()->create([
            'project_id' => $this->project->id,
            'requested_by' => $this->admin->id,
            'insight_type' => 'project_summary',
            'status' => 'queued',
        ]);

        $this->mock(InsightGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andReturn([
                'result' => [
                    'summary' => 'The project has open work.',
                    'findings' => [[
                        'title' => 'Open issues present',
                        'evidence' => ['summary.open_issues', 'invented_metric'],
                        'interpretation' => 'There is work in flight.',
                        'confidence' => 'high',
                    ]],
                    'recommendations' => [[
                        'title' => 'Review the backlog',
                        'reason' => 'Open work should be triaged.',
                        'action_type' => 'human_review',
                    ]],
                    'limitations' => ['Small sample size.'],
                ],
                'provider' => 'gemini',
                'model' => 'gemini-test',
                'usage' => null,
            ]);
        });

        GenerateProjectInsights::dispatchSync($insight->id);

        $insight->refresh();

        $this->assertSame('completed', $insight->status);
        $this->assertSame('gemini', $insight->model_provider);
        $this->assertSame('gemini-test', $insight->model_name);
        $this->assertNotNull($insight->metrics_snapshot);
        $this->assertSame(1, $insight->metrics_snapshot['summary']['open_issues']);
        $this->assertSame(
            ['summary.open_issues'],
            $insight->result_json['findings'][0]['evidence'],
        );
        $this->assertNotNull($insight->generated_at);
    }

    public function test_validator_rejects_missing_summary_and_drops_unknown_evidence(): void
    {
        $validator = new InsightValidator;

        $validated = $validator->validate([
            'summary' => 'A concise summary.',
            'findings' => [[
                'title' => 'Title',
                'evidence' => ['summary.total_issues', 'not_real'],
                'interpretation' => 'Interpretation',
                'confidence' => 'bogus',
            ]],
            'recommendations' => [],
            'limitations' => ['One'],
        ], ['summary' => ['total_issues' => 5]]);

        $this->assertSame(['summary.total_issues'], $validated['findings'][0]['evidence']);
        $this->assertSame('low', $validated['findings'][0]['confidence']);

        $this->expectException(ValidationException::class);

        $validator->validate(['summary' => ''], ['summary' => []]);
    }
}
