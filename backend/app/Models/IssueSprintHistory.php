<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueSprintHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'issue_id',
        'from_sprint_id',
        'to_sprint_id',
        'changed_by',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function fromSprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'from_sprint_id');
    }

    public function toSprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class, 'to_sprint_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
