<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitHubRepository extends Model
{
    protected $table = 'github_repositories';

    protected $fillable = [
        'github_repository_id',
        'github_installation_id',
        'owner_login',
        'name',
        'full_name',
        'html_url',
        'default_branch',
        'is_private',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function installation(): BelongsTo
    {
        return $this->belongsTo(GitHubInstallation::class, 'github_installation_id');
    }

    public function pullRequests(): HasMany
    {
        return $this->hasMany(GitHubPullRequest::class, 'github_repository_id');
    }

    public function commits(): HasMany
    {
        return $this->hasMany(GitHubCommit::class, 'github_repository_id');
    }

    public function issueLinks(): HasMany
    {
        return $this->hasMany(GitHubIssueLink::class, 'github_repository_id');
    }

    public function checkRuns(): HasMany
    {
        return $this->hasMany(GitHubCheckRun::class, 'github_repository_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'github_project_repositories', 'github_repository_id', 'project_id')
            ->withPivot(['linked_by', 'is_default', 'settings'])
            ->withTimestamps();
    }
}
