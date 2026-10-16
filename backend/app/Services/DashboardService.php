<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DashboardService
{

    public function getStats(?User $user = null): array
    {
        $user ??= auth()->user();

        return [
            'total_projects' => $this->getTotalProjects($user),
            'total_issues' => $this->getTotalIssues($user),
            'open_issues' => $this->getOpenIssues($user),
            'in_progress_issues' => $this->getInProgressIssues($user),
            'closed_issues' => $this->getClosedIssues($user),
            'critical_issues' => $this->getCriticalIssues($user),
            'recent_activity' => $this->getRecentActivity(10, $user),
        ];
    }

    protected function baseProjectQuery(?User $user = null)
    {
        $query = Project::query();

        if ($user && !$user->isAdmin()) {
            $query->visibleTo($user);
        }

        return $query;
    }

    protected function baseIssueQuery(?User $user = null)
    {
        $query = Issue::query();

        if ($user && !$user->isAdmin()) {
            $query->visibleTo($user);
        }

        return $query;
    }

    protected function getTotalProjects(?User $user = null): int
    {
        return $this->baseProjectQuery($user)->count();
    }

    protected function getTotalIssues(?User $user = null): int
    {
        return $this->baseIssueQuery($user)->count();
    }

    protected function getOpenIssues(?User $user = null): int
    {
        return $this->baseIssueQuery($user)->whereNotIn('status', ['resolved', 'closed'])->count();
    }

    protected function getInProgressIssues(?User $user = null): int
    {
        return $this->baseIssueQuery($user)->where('status', 'in_progress')->count();
    }

    protected function getClosedIssues(?User $user = null): int
    {
        return $this->baseIssueQuery($user)->where('status', 'closed')->count();
    }

    protected function getCriticalIssues(?User $user = null): int
    {
        return $this->baseIssueQuery($user)->where('priority', 'critical')->count();
    }

    /**
     * Get recent activity from multiple sources (comments, issue updates, ect.)
     */

    protected function getRecentActivity(int $limit = 10, ?User $user = null): array
    {

        try {
            $comments = Comment::with(['user', 'issue.project'])
                ->when($user && !$user->isAdmin(), function ($query) use ($user) {
                    $query->whereHas('issue.project', function ($projectQuery) use ($user) {
                        $projectQuery->visibleTo($user);
                    });
                })
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
                ->when($user && !$user->isAdmin(), function ($query) use ($user) {
                    $query->visibleTo($user);
                })
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
                ->when($user && !$user->isAdmin(), function ($query) use ($user) {
                    $query->where(function ($innerQuery) use ($user) {
                        $innerQuery->whereHas('project', function ($projectQuery) use ($user) {
                            $projectQuery->visibleTo($user);
                        })
                            ->orWhereHas('issue.project', function ($projectQuery) use ($user) {
                                $projectQuery->visibleTo($user);
                            });
                    });
                })
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
