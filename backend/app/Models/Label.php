<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Label extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'normalized_name',
        'color',
        'description',
        'created_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'issue_label', 'label_id', 'issue_id')
            ->withPivot('created_by', 'created_at');
    }

    /**
     * Canonical form used to enforce per-project uniqueness case-insensitively.
     */
    public static function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name));
    }
}
