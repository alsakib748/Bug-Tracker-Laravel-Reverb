<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

class DashboardService
{

    public function getStats(): array
    {
        return [
            'total_projects' => $this->getTotalProjects(),
            'total_issues' => $this->getTotalIssues(),
            'open_issues' => $this->getOpenIssues(),
            'in_progress_issues' => $this->getInProgressIssues(),
            'closed_issues' => $this->getClosedIssues(),
            'critical_issues' => $this->getCriticalIssues(),
            'recent_activity' => $this->getRecentActivity(10),
        ];
    }

    protected function getTotalProjects(): int
    {
        return Project::count();
    }

    protected function getTotalIssues(): int
    {
        return Issue::count();
    }

    protected function getOpenIssues(): int
    {
        return Issue::whereNotIn('status', ['resolved', 'closed'])->count();
    }

    protected function getInProgressIssues(): int
    {
        return Issue::where('status', 'in_progress')->count();
    }

    protected function getClosedIssues(): int
    {
        return Issue::where('status', 'closed')->count();
    }

    protected function getCriticalIssues(): int
    {
        return Issue::where('priority', 'critical')->count();
    }

    /**
     * Get recent activity from multiple sources (comments, issue updates, ect.)
     */

    protected function getRecentActivity(int $limit = 10): array
    {

        try {
            $comments = Comment::with(['user', 'issue'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($comment) {
                    return [
                        'type' => 'comment',
                        'user' => $comment->user->name ?? 'Unknown',
                        'description' => "commented on issue #{$comment->issue->id}: " . substr($comment->comment, 0, 50) . (strlen($comment->comment) > 50 ? '...' : ''),
                        'created_at' => $comment->created_at->toISOString(),
                    ];
                });

            // Recent issue creations
            $issues = Issue::with(['reporter', 'project'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($issue) {
                    return [
                        'type' => 'issue_created',
                        'user' => $issue->reporter->name ?? 'Unknown',
                        'description' => "created issue #{$issue->id} : {$issue->title} in project {$issue->project->name}",
                        'created_at' => $issue->created_at->toISOString(),
                    ];
                });

            // Combine and sort by created_by descending, then take $limit
            // $activities = $comments->concat($issues)
            //     ->sortByDesc('created_at')
            //     ->take($limit)
            //     ->values()
            //     ->toArray();

            // return $activities;

            $activities = ActivityLog::with(['user', 'project', 'issue'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($log) {
                    return [
                        'type' => $log->action,
                        'user' => $log->user->name ?? 'System',
                        'description' => $log->description,
                        'created_at' => $log->created_at->toISOString(),
                    ];
                })
                ->toArray();

            return $activities;
        } catch (\Exception $e) {
            Log::error('Dashboard recent activity error: ' . $e->getMessage());
            return [];
        }

    }

}