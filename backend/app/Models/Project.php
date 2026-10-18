<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
    ];

    /**
     * * Creator (User)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * * Members (many-to-many)
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * * Issues belonging to this project
     */
    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * * Labels defined for this project
     */
    public function labels()
    {
        return $this->hasMany(Label::class);
    }

    /**
     * * Sprints belonging to this project
     */
    public function sprints()
    {
        return $this->hasMany(Sprint::class);
    }

    /**
     * * The single active sprint for this project (if any)
     */
    public function activeSprint()
    {
        return $this->hasOne(Sprint::class)->where('status', 'active');
    }

    /**
     * * Activity logs related to this project
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * * Automation rules configured for this project
     */
    public function automationRules()
    {
        return $this->hasMany(AutomationRule::class);
    }

    /**
     * * Automation execution records for this project
     */
    public function automationExecutions()
    {
        return $this->hasMany(AutomationExecution::class);
    }

    /**
     * * AI-generated project insights for this project
     */
    public function insights()
    {
        return $this->hasMany(ProjectInsight::class);
    }

    /**
     * * GitHub repositories linked to this project
     */
    public function githubRepositories()
    {
        return $this->belongsToMany(GitHubRepository::class, 'github_project_repositories', 'project_id', 'github_repository_id')
            ->withPivot(['linked_by', 'is_default', 'settings'])
            ->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('members', function (Builder $memberQuery) use ($user) {
            $memberQuery->where('users.id', $user->id);
        });
    }

    public function isVisibleTo(User $user): bool
    {
        return $user->isAdmin() || $this->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Whether the user manages this project (creator, manager member, or admin).
     */
    public function isManagedBy(User $user): bool
    {
        if ($user->isAdmin() || $this->created_by === $user->id) {
            return true;
        }

        return $this->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'manager')
            ->exists();
    }
}
