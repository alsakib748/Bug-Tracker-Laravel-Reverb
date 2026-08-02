<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Models\ActivityLog;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'status',
        'created_by'
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
    ];

    /**
     * * Creator (User)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * * Members (many-to-many)
     */
    public function members()
    {
        // return $this->belongsToMany(User::class, 'project_members')
        //     ->withPivot('role', 'joined_at')
        //     ->withTimestamps();
        return $this->belongsToMany(User::class, 'project_members');
    }

    /**
     * * Issues under this project
     */
    public function issues()
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * * Activity logs related to this project
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

}
