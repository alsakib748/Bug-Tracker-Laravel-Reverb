<?php

namespace App\Models;

use App\Enums\GitHubEntityType;
use App\Enums\GitHubLinkSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitHubIssueLink extends Model
{
    protected $table = 'github_issue_links';

    protected $fillable = [
        'issue_id',
        'github_repository_id',
        'entity_type',
        'github_entity_id',
        'external_number',
        'external_sha',
        'external_url',
        'linked_by',
        'link_source',
    ];

    protected $casts = [
        'entity_type' => GitHubEntityType::class,
        'link_source' => GitHubLinkSource::class,
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(GitHubRepository::class, 'github_repository_id');
    }

    public function linkedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }
}
