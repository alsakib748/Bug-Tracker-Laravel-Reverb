<?php

namespace Database\Seeders;

use App\Models\Issue;
use App\Models\IssueSprintHistory;
use App\Models\Label;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds a realistic, human-readable agile project so the Kanban board and
 * backlog render with meaningful content (sprints, story points, labels,
 * assignees and workflow progress).
 *
 * Run with:  php artisan db:seed --class=BoardDemoSeeder
 */
class BoardDemoSeeder extends Seeder
{
    private User $admin;

    /** @var Collection<int, User> */
    private Collection $developers;

    /** @var Collection<int, User> */
    private Collection $testers;

    /** @var array<string, Label> */
    private array $labels = [];

    public function run(): void
    {
        $this->admin = User::query()
            ->where('email', 'admin@gmail.com')
            ->first()
            ?? User::query()->where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->developers = User::query()->where('role', 'developer')->orderBy('id')->take(4)->get();
        $this->testers = User::query()->where('role', 'tester')->orderBy('id')->take(2)->get();

        // Make the run repeatable.
        Project::query()->where('code', 'AUR')->delete();

        $project = Project::query()->create([
            'name' => 'Aurora E-Commerce',
            'code' => 'AUR',
            'description' => 'Customer-facing storefront: search, checkout and post-purchase experience.',
            'color' => '#4f46e5',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->attachMembers($project);
        $this->createLabels($project);

        [$completed, $active, $planned] = $this->createSprints($project);

        $this->seedCompletedSprint($project, $completed);
        $this->seedActiveSprint($project, $active);
        $this->seedBacklog($project);

        $this->command->info('Board demo data seeded for project "Aurora E-Commerce" (AUR).');
        $this->command->info("  Active sprint: {$active->name}");
        $this->command->info('  Issues: '.$project->issues()->count());
    }

    private function attachMembers(Project $project): void
    {
        $project->members()->attach($this->admin->id, [
            'role' => 'manager',
            'joined_at' => now()->subMonths(3),
        ]);

        foreach ($this->developers as $developer) {
            $project->members()->attach($developer->id, [
                'role' => 'developer',
                'joined_at' => now()->subMonths(2)->addDays(rand(0, 20)),
            ]);
        }

        foreach ($this->testers as $tester) {
            $project->members()->attach($tester->id, [
                'role' => 'tester',
                'joined_at' => now()->subMonths(2)->addDays(rand(0, 20)),
            ]);
        }
    }

    private function createLabels(Project $project): void
    {
        $definitions = [
            'frontend' => ['#3b82f6', 'UI / browser side work'],
            'backend' => ['#8b5cf6', 'APIs, jobs and domain logic'],
            'design' => ['#ec4899', 'Visual / UX changes'],
            'performance' => ['#f59e0b', 'Speed and efficiency'],
            'security' => ['#ef4444', 'Auth, payments and data safety'],
            'tech-debt' => ['#6b7280', 'Refactors and cleanup'],
            'documentation' => ['#14b8a6', 'Docs and guides'],
        ];

        foreach ($definitions as $name => [$color, $description]) {
            $this->labels[$name] = $project->labels()->create([
                'name' => $name,
                'normalized_name' => Label::normalizeName($name),
                'color' => $color,
                'description' => $description,
                'created_by' => $this->admin->id,
            ]);
        }
    }

    /**
     * @return array{0: Sprint, 1: Sprint, 2: Sprint}
     */
    private function createSprints(Project $project): array
    {
        $completed = Sprint::query()->create([
            'project_id' => $project->id,
            'name' => 'Sprint 11 · Checkout & Payments',
            'goal' => 'Launch saved cards and stabilise the payment gateway.',
            'status' => 'completed',
            'start_date' => now()->subWeeks(4)->toDateString(),
            'end_date' => now()->subWeeks(2)->toDateString(),
            'started_at' => now()->subWeeks(4),
            'completed_at' => now()->subWeeks(2),
            'created_by' => $this->admin->id,
        ]);

        $active = Sprint::query()->create([
            'project_id' => $project->id,
            'name' => 'Sprint 12 · Search & Discovery',
            'goal' => 'Make product search fast, relevant and filterable.',
            'status' => 'active',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(9)->toDateString(),
            'started_at' => now()->subDays(5),
            'created_by' => $this->admin->id,
        ]);

        $planned = Sprint::query()->create([
            'project_id' => $project->id,
            'name' => 'Sprint 13 · Notifications & Emails',
            'goal' => 'Transactional emails and in-app notification centre.',
            'status' => 'planned',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(24)->toDateString(),
            'created_by' => $this->admin->id,
        ]);

        return [$completed, $active, $planned];
    }

    private function seedCompletedSprint(Project $project, Sprint $sprint): void
    {
        $rows = [
            ['Apple Pay button not rendering on Safari', 'bug', 'high', 'major', 3, ['frontend', 'security'], 'closed'],
            ['Gateway timeout during 3-D Secure challenge', 'bug', 'critical', 'blocker', 8, ['backend', 'security'], 'closed'],
            ['Add saved cards to one-click checkout', 'feature', 'high', 'major', 5, ['backend', 'frontend'], 'closed'],
            ['Coupon codes can be stacked indefinitely', 'bug', 'high', 'critical', 3, ['backend'], 'closed'],
            ['Order confirmation email missing grand total', 'bug', 'medium', 'major', 2, ['backend'], 'closed'],
            ['Support PayPal as an alternative provider', 'feature', 'high', 'major', 8, ['backend'], 'closed'],
            ['Cart total rounding error on large orders', 'bug', 'medium', 'major', 2, ['backend'], 'closed'],
            ['Refactor payment provider interface', 'task', 'medium', 'minor', 5, ['tech-debt', 'backend'], 'closed'],
            ['Checkout validation messages are unclear', 'bug', 'low', 'minor', 2, ['frontend', 'design'], 'resolved'],
            ['Add unit tests for the tax calculator', 'task', 'medium', 'minor', 3, ['backend', 'documentation'], 'closed'],
        ];

        foreach ($rows as $index => $row) {
            $this->createIssue($project, $sprint, $row, $index);
        }
    }

    private function seedActiveSprint(Project $project, Sprint $sprint): void
    {
        // Ordered by board column so board_position reads top-to-bottom.
        $rows = [
            // open
            ['Product filters reset when the page reloads', 'bug', 'high', 'major', 3, ['frontend', 'backend'], 'open'],
            ['Add keyboard navigation to search suggestions', 'improvement', 'medium', 'minor', 2, ['frontend', 'design'], 'open'],
            // assigned
            ['Search results ignore the selected category', 'bug', 'high', 'critical', 5, ['backend'], 'assigned'],
            ['Typography mismatch on filter chips', 'bug', 'low', 'minor', 1, ['design', 'frontend'], 'assigned'],
            ['Autocomplete endpoint returns stale results', 'bug', 'medium', 'major', 3, ['backend'], 'assigned'],
            ['Add analytics events for zero-result searches', 'feature', 'medium', 'minor', 2, ['backend'], 'assigned'],
            // in_progress
            ['Implement faceted search for price ranges', 'feature', 'high', 'major', 8, ['backend'], 'in_progress'],
            ['Pagination loses query parameters', 'bug', 'high', 'major', 3, ['frontend'], 'in_progress'],
            ['Tune relevance ranking for product results', 'improvement', 'medium', 'minor', 5, ['backend'], 'in_progress'],
            ['Loading skeleton flashes on fast connections', 'bug', 'low', 'minor', 1, ['frontend', 'design'], 'in_progress'],
            // code_review
            ['Refactor the search index builder service', 'task', 'medium', 'minor', 5, ['tech-debt', 'backend'], 'code_review'],
            ['Add unit tests for the filter parser', 'task', 'medium', 'minor', 3, ['backend', 'documentation'], 'code_review'],
            // testing
            ['Zero-result page shows a raw error message', 'bug', 'high', 'major', 3, ['frontend'], 'testing'],
            ['Search API rate limit is too aggressive', 'bug', 'medium', 'major', 5, ['backend', 'performance'], 'testing'],
            ['Highlight matching terms in result snippets', 'feature', 'low', 'minor', 2, ['frontend'], 'testing'],
            // resolved
            ['Default sort by relevance is not applied', 'bug', 'high', 'major', 3, ['backend'], 'resolved'],
            ['Mobile filter drawer does not close', 'bug', 'medium', 'major', 2, ['frontend', 'design'], 'resolved'],
            ['Search box loses focus after submitting', 'bug', 'low', 'minor', 1, ['frontend'], 'resolved'],
            // closed
            ['Search results page shows the wrong result count', 'bug', 'medium', 'major', 2, ['frontend'], 'closed'],
            ['Remove the deprecated search v1 endpoint', 'task', 'low', 'minor', 2, ['tech-debt', 'backend'], 'closed'],
            ['Update the search index schema documentation', 'task', 'low', 'minor', 1, ['documentation'], 'closed'],
        ];

        foreach ($rows as $index => $row) {
            $this->createIssue($project, $sprint, $row, $index);
        }
    }

    private function seedBacklog(Project $project): void
    {
        $rows = [
            ['Support saved searches per user', 'feature', 'medium', 'minor', 5, ['backend', 'frontend']],
            ['Recommend recently viewed products', 'feature', 'low', 'minor', 8, ['backend']],
            ['Add a synonym management screen for search', 'feature', 'medium', 'minor', 5, ['frontend', 'backend']],
            ['Reduce search index rebuild time', 'improvement', 'high', 'major', 8, ['performance', 'backend']],
            ['Add A/B test hooks to ranking', 'improvement', 'low', 'minor', 5, ['backend']],
            ['Internationalise search placeholders', 'task', 'medium', 'minor', 2, ['frontend', 'documentation']],
            ['Fix memory spike during bulk reindex', 'bug', 'high', 'critical', 8, ['backend', 'performance']],
            ['Expose search metrics on the admin dashboard', 'feature', 'medium', 'minor', 3, ['frontend', 'backend']],
            ['Dark-mode contrast on the filter panel', 'bug', 'low', 'minor', 1, ['design', 'frontend']],
            ['Migrate search logs to cold storage', 'task', 'medium', 'minor', 5, ['tech-debt', 'backend']],
        ];

        foreach ($rows as $index => $row) {
            $this->createIssue($project, null, $row, $index);
        }
    }

    /**
     * @param  array{0: string, 1: string, 2: string, 3: string, 4: int, 5: array<int, string>, 6: string}  $row
     */
    private function createIssue(Project $project, ?Sprint $sprint, array $row, int $position): void
    {
        [$title, $type, $priority, $severity, $points, $labelNames, $status] = [
            $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6] ?? 'open',
        ];

        $isDone = in_array($status, ['resolved', 'closed'], true);
        $assignee = $this->pickAssignee($status);

        $createdAt = now()->subDays(rand(8, 30));
        $issue = Issue::query()->create([
            'project_id' => $project->id,
            'sprint_id' => $sprint?->id,
            'reporter_id' => $this->testers->first()?->id ?? $this->admin->id,
            'assigned_to' => $assignee?->id,
            'title' => $title,
            'description' => $this->descriptionFor($title),
            'priority' => $priority,
            'severity' => $severity,
            'status' => $status,
            'type' => $type,
            'due_date' => $sprint?->end_date,
            'estimated_hours' => $points * 2,
            'story_points' => $points,
            'backlog_position' => $sprint ? null : $position,
            'board_position' => $sprint ? $position : null,
            'version' => rand(0, 6),
            'completed_at' => $isDone ? now()->subDays(rand(1, 10)) : null,
        ]);

        // created_at / updated_at are not mass-assignable, so backdate directly.
        $issue->created_at = $createdAt;
        $issue->updated_at = $isDone ? now()->subDays(rand(0, 3)) : now()->subHours(rand(1, 40));

        if ($status === 'resolved') {
            $issue->resolved_at = $issue->completed_at;
        } elseif ($status === 'closed') {
            $issue->closed_at = $issue->completed_at;
            $issue->resolved_at = $issue->completed_at?->copy()->subDay();
        }

        $issue->save();

        // Realistic status history: created + transition to the current column.
        $issue->statusHistories()->create([
            'from_status' => null,
            'to_status' => 'open',
            'changed_by' => $issue->reporter_id,
            'reason' => 'Issue created',
            'created_at' => $createdAt,
        ]);

        if ($status !== 'open') {
            $issue->statusHistories()->create([
                'from_status' => 'open',
                'to_status' => $status,
                'changed_by' => $assignee?->id ?? $this->admin->id,
                'reason' => null,
                'created_at' => $createdAt->copy()->addHours(rand(2, 72)),
            ]);
        }

        if ($sprint) {
            IssueSprintHistory::query()->create([
                'issue_id' => $issue->id,
                'from_sprint_id' => null,
                'to_sprint_id' => $sprint->id,
                'changed_by' => $this->admin->id,
                'reason' => 'Added to sprint',
                'created_at' => $sprint->started_at ?? $createdAt,
            ]);
        }

        $labelIds = collect($labelNames)
            ->map(fn (string $name) => $this->labels[$name]->id)
            ->all();

        $issue->labels()->attach($labelIds, ['created_by' => $this->admin->id, 'created_at' => now()]);
    }

    private function pickAssignee(string $status): ?User
    {
        if (in_array($status, ['open'], true)) {
            return null;
        }

        $pool = $this->developers->isNotEmpty() ? $this->developers : $this->testers;

        return $pool->random();
    }

    private function descriptionFor(string $title): string
    {
        return "### Context\n\n{$title}.\n\n### Steps to reproduce / acceptance criteria\n\n1. Navigate to the affected screen.\n2. Perform the action described in the title.\n3. Observe the current behaviour and compare it with the expectation.\n\n### Notes\n\nRaised from sprint planning; please keep the linked issues up to date.";
    }
}
