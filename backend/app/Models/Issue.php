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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
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
        'completed_at'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'estimated_hours' => 'float',
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

    // ---------- Scopes  ----------
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'assigned', 'in_progress', 'code_review', 'testing', 'reopened']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

}