<?php

namespace App\Models;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'issue_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'properties',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    // ---- Accessors ----
    public function getActionLabelAttribute(): string
    {
        return \App\Enums\ActivityAction::tryFrom($this->action)?->label() ?? $this->action;
    }

}
