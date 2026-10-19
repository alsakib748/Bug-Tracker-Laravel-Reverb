<?php

namespace Tests\Feature;

use App\Events\IssueStatusChanged;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Verifies the broadcast contract for status changes.
 *
 * Uses {@see DatabaseMigrations} (no wrapping transaction) so that broadcast
 * events registered via DB::afterCommit actually fire, mirroring production.
 */
class RealtimeBroadcastTest extends TestCase
{
    use DatabaseMigrations;

    private function makeIssue(User $actor, string $status = 'in_progress'): Issue
    {
        $project = Project::factory()->create(['created_by' => $actor->id]);
        $project->members()->attach($actor->id, [
            'role' => 'developer',
            'joined_at' => now(),
        ]);

        return Issue::factory()->create([
            'project_id' => $project->id,
            'reporter_id' => $actor->id,
            'status' => $status,
        ]);
    }

    public function test_successful_transition_dispatches_status_changed_event(): void
    {
        $admin = User::factory()->admin()->create();
        $issue = $this->makeIssue($admin, 'in_progress');

        Sanctum::actingAs($admin);
        Event::fake([IssueStatusChanged::class]);

        $this->postJson("/api/issues/{$issue->id}/transitions", [
            'to_status' => 'code_review',
            'reason' => 'Ready for review',
        ])->assertOk();

        Event::assertDispatched(IssueStatusChanged::class, function (IssueStatusChanged $event) use ($issue) {
            $channels = array_map(fn ($channel) => $channel->name, $event->broadcastOn());

            return $event->issue->id === $issue->id
                && $event->oldStatus === 'in_progress'
                && $event->newStatus === 'code_review'
                && $event->broadcastAs() === 'issue.status.changed'
                && $event->broadcastWith()['new_status'] === 'code_review'
                && in_array('private-issue.' . $issue->id, $channels, true)
                && in_array('private-project.' . $issue->project_id, $channels, true);
        });
    }

    public function test_failed_transition_dispatches_no_event(): void
    {
        $admin = User::factory()->admin()->create();
        $issue = $this->makeIssue($admin, 'open');

        Sanctum::actingAs($admin);
        Event::fake([IssueStatusChanged::class]);

        $this->postJson("/api/issues/{$issue->id}/transitions", [
            'to_status' => 'closed',
        ])->assertStatus(422);

        Event::assertNotDispatched(IssueStatusChanged::class);
    }
}
