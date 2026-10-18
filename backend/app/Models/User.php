<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


#[Fillable(['name', 'email', 'password', 'avatar', 'last_seen', 'status'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_seen' => 'datetime',
            'status' => UserStatus::class,
        ];
    }

    /**
     * *Projects created by this user
     */
    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * *Issues reported by this user
     */
    public function reportedIssues()
    {
        return $this->hasMany(Issue::class, 'reporter_id');
    }

    /**
     * * Issues assigned to this user
     */
    public function assignedIssues()
    {
        return $this->hasMany(Issue::class, 'assigned_to');
    }

    /**
     * * Projects where this user is a member
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * * Comments written by this user
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * * Attachments uploaded by this user
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * * Issues this user is watching
     */
    public function watchedIssues()
    {
        return $this->belongsToMany(Issue::class, 'issue_watchers', 'user_id', 'issue_id')
            ->withPivot('created_at');
    }

    /**
     * * Activity logs
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Canonical role names used across the application.
     */
    public const ROLE_ADMIN = 'Admin';

    public const ROLE_PROJECT_MANAGER = 'Project Manager';

    public const ROLE_DEVELOPER = 'Developer';

    public const ROLE_QA_TESTER = 'QA Tester';

    public const ROLE_REPORTER = 'Reporter';

    public const ROLE_VIEWER = 'Viewer';

}