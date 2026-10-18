<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_rule_id',
        'rule_version',
        'project_id',
        'event_id',
        'event_type',
        'subject_type',
        'subject_id',
        'status',
        'matched_conditions',
        'error_code',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'matched_conditions' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'rule_version' => 'integer',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationActionExecution::class);
    }
}
