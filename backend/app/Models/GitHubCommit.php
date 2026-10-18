<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitHubCommit extends Model
{
    protected $table = 'github_commits';

    protected $fillable = [
        'github_repository_id',
        'sha',
        'message_summary',
        'author_login',
        'committed_at',
        'html_url',
    ];

    protected $casts = [
        'committed_at' => 'datetime',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitHubRepository::class, 'github_repository_id');
    }
}
