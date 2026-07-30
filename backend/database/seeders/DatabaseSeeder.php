<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Admin
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('11111111'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // 5 developers
        $developers = User::factory(5)->developer()->create();

        // 10 testers
        $testers = User::factory(10)->tester()->create();

        // Additional random users (mix of roles) for more data
        $randomUsers = User::factory(5)->create();

        // Combine all users for later use
        $allUsers = User::all();

        // ---------- 2. Create projects ----------
        $projects = Project::factory(5)->create([
            'created_by' => 1,
        ]);

        // ---------- 3. Add members to projects ----------
        foreach ($projects as $project) {
            // Each project gets 3 to 6 members
            $memberCount = rand(3, 6);
            $candidates = $allUsers->random($memberCount);

            foreach ($candidates as $user) {
                $role = match ($user->role) {
                    'admin' => 'manager',
                    'developer' => 'developer',
                    'tester' => 'tester',
                    default => 'developer',
                };
                $project->members()->attach($user->id, [
                    'role' => $role,
                    'joined_at' => now()->subDays(rand(1, 30)),
                ]);
            }

            // Ensure admin is always a member
            if (!$project->members->contains($admin)) {
                $project->members()->attach($admin->id, [
                    'role' => 'manager',
                    'joined_at' => now(),
                ]);
            }
        }

        // ---------- 4. Create issues ----------
        $issues = collect();
        foreach ($projects as $project) {
            $projectMembers = $project->members;

            // 20 to 50 issues per project
            $issueCount = rand(20, 50);
            for ($i = 0; $i < $issueCount; $i++) {
                $reporter = $projectMembers->random();
                $assignee = $projectMembers->random();

                // Random status distribution
                $statuses = ['open', 'assigned', 'in_progress', 'code_review', 'testing', 'resolved', 'closed', 'reopened'];
                $status = $statuses[array_rand($statuses)];

                $issue = Issue::factory()->create([
                    'project_id' => $project->id,
                    'reporter_id' => $reporter->id,
                    'assigned_to' => (in_array($status, ['assigned', 'in_progress', 'code_review', 'testing']) || rand(1, 10) > 3)
                        ? $assignee->id
                        : null,
                    'status' => $status,
                ]);

                // If resolved or closed, set completed_at
                if (in_array($status, ['resolved', 'closed'])) {
                    $issue->completed_at = now()->subDays(rand(1, 20));
                    $issue->save();
                }

                $issues->push($issue);

                // ---------- 5. Create comments for each issue ----------
                $commentCount = rand(2, 5);
                for ($j = 0; $j < $commentCount; $j++) {
                    $commentUser = $projectMembers->random();
                    Comment::factory()->create([
                        'issue_id' => $issue->id,
                        'user_id' => $commentUser->id,
                    ]);
                }

                // ---------- 6. Create attachments for some issues ----------
                if (rand(1, 3) == 1) { // 1/3 chance
                    $attachmentsCount = rand(1, 2);
                    for ($k = 0; $k < $attachmentsCount; $k++) {
                        Attachment::factory()->create([
                            'issue_id' => $issue->id,
                            'user_id' => $projectMembers->random()->id,
                        ]);
                    }
                }

                // ---------- 7. Create activity logs for each issue ----------
                $logCount = rand(1, 3);
                for ($l = 0; $l < $logCount; $l++) {
                    ActivityLog::factory()->create([
                        'issue_id' => $issue->id,
                        'project_id' => $project->id,
                        'user_id' => $projectMembers->random()->id,
                    ]);
                }

            }
        }


        // ---------- 10. Create some activity logs for projects ----------
        foreach ($projects as $project) {
            ActivityLog::factory(5)->create([
                'project_id' => $project->id,
                'issue_id' => null,
                'user_id' => $project->members->random()->id,
                'action' => 'project_updated',
            ]);
        }

        $this->command->info('Database seeded with realistic bug tracker data!');

    }
}
