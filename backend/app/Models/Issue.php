<?php

namespace App\Models;

use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Enums\Priority;
use App\Enums\Severity;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'sprint_id',
        'reporter_id',
        'assigned_to',
        'title',
        'description',
        'priority',
        'severity',
        'status',
        'type',
        'due_date',
        'estimated_hours',
        'story_points',
        'backlog_position',
        'board_position',
        'completed_at'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'estimated_hours' => 'float',
        'story_points' => 'integer',
        'backlog_position' => 'integer',
        'board_position' => 'integer',
        'version' => 'integer',
        'status' => IssueStatus::class,
        'priority' => Priority::class,
        'severity' => Severity::class,
        'type' => IssueType::class,
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(IssueStatusHistory::class)
            ->orderBy('created_at');
    }

    public function labels()
    {
        return $this->belongsToMany(Label::class, 'issue_label', 'issue_id', 'label_id')
            ->withPivot('created_by', 'created_at')
            ->orderBy('name');
    }

    public function watchers()
    {
        return $this->belongsToMany(User::class, 'issue_watchers', 'issue_id', 'user_id')
            ->withPivot('created_at');
    }

    public function outgoingLinks()
    {
        return $this->hasMany(IssueLink::class, 'source_issue_id');
    }

    public function incomingLinks()
    {
        return $this->hasMany(IssueLink::class, 'target_issue_id');
    }

    public function githubLinks()
    {
        return $this->hasMany(GitHubIssueLink::class);
    }

    public function sprint()
    {
        return $this->belongsTo(Sprint::class);
    }

    public function sprintHistories()
    {
        return $this->hasMany(IssueSprintHistory::class)->orderBy('created_at');
    }

    // ---------- Scopes  ----------
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'assigned', 'in_progress', 'code_review', 'testing', 'reopened']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('project.members', function (Builder $memberQuery) use ($user) {
            $memberQuery->where('users.id', $user->id);
        });
    }

    public function isVisibleTo(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->project && $this->project->members()->where('users.id', $user->id)->exists();
    }

}