<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitHubCheckRun extends Model
{
    protected $table = 'github_check_runs';

    protected $fillable = [
        'github_repository_id',
        'github_check_run_id',
        'name',
        'head_sha',
        'status',
        'conclusion',
        'html_url',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitHubRepository::class, 'github_repository_id');
    }
}
