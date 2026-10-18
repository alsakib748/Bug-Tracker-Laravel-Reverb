<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitHubInstallation extends Model
{
    protected $table = 'github_installations';

    protected $fillable = [
        'github_installation_id',
        'account_login',
        'account_type',
        'status',
        'installed_at',
        'last_verified_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    public function repositories(): HasMany
    {
        return $this->hasMany(GitHubRepository::class, 'github_installation_id');
    }
}
