<?php

namespace App\Models;

use App\Enums\IssueLinkType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueLink extends Model
{
    use HasFactory;

    protected $table = 'issue_links';

    public $timestamps = false;

    protected $fillable = [
        'source_issue_id',
        'target_issue_id',
        'link_type',
        'created_by',
    ];

    protected $casts = [
        'link_type' => IssueLinkType::class,
        'created_at' => 'datetime',
    ];

    public function sourceIssue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'source_issue_id');
    }

    public function targetIssue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'target_issue_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
