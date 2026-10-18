<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationActionExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_execution_id',
        'action_key',
        'action_type',
        'status',
        'attempt_count',
        'idempotency_key',
        'input_summary',
        'result_summary',
        'error_code',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'input_summary' => 'array',
        'result_summary' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'attempt_count' => 'integer',
    ];

    public function execution(): BelongsTo
    {
        return $this->belongsTo(AutomationExecution::class, 'automation_execution_id');
    }
}
