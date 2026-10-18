<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitHubPullRequest extends Model
{
    protected $table = 'github_pull_requests';

    protected $fillable = [
        'github_repository_id',
        'github_pr_id',
        'number',
        'title',
        'body',
        'html_url',
        'state',
        'is_merged',
        'draft',
        'head_ref',
        'base_ref',
        'head_sha',
        'base_sha',
        'author_login',
        'opened_at',
        'merged_at',
        'closed_at',
        'last_synced_at',
    ];

    protected $casts = [
        'is_merged' => 'boolean',
        'draft' => 'boolean',
        'opened_at' => 'datetime',
        'merged_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitHubRepository::class, 'github_repository_id');
    }

    public function issueLinks(): HasMany
    {
        return $this->hasMany(GitHubIssueLink::class, 'github_entity_id', 'github_pr_id')
            ->where('entity_type', 'pull_request');
    }
}
