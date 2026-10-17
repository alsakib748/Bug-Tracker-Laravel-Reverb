<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiBugReportSuggestion extends Model
{
    use HasFactory;

    protected $table = 'ai_bug_report_suggestions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'issue_id',
        'requested_by',
        'status',
        'input_snapshot',
        'input_hash',
        'suggestions',
        'review_decisions',
        'applied_fields',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'error_code',
        'started_at',
        'completed_at',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input_snapshot' => 'array',
            'suggestions' => 'array',
            'review_decisions' => 'array',
            'applied_fields' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Whether the analysis has suggestions a user can review and apply.
     */
    public function isReviewable(): bool
    {
        return in_array($this->status, ['ready', 'partially_applied'], true);
    }

    /**
     * Whether the analysis has reached its expiry while still actionable.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isPast()
            && in_array($this->status, ['queued', 'processing', 'ready', 'partially_applied'], true);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
            'error_code' => 'expired',
            'completed_at' => now(),
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_code' => null,
        ]);
    }

    public function markAsReady(array $suggestions, ?int $inputTokens = null, ?int $outputTokens = null, ?string $provider = null, ?string $model = null): void
    {
        $this->update([
            'status' => 'ready',
            'suggestions' => $suggestions,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'provider' => $provider,
            'model' => $model,
            'error_code' => null,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorCode): void
    {
        $this->update([
            'status' => 'failed',
            'error_code' => $errorCode,
            'completed_at' => now(),
        ]);
    }
}
