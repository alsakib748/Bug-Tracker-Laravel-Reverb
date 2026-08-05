<?php

namespace App\Models;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'user_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type'
    ];

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessor for download URL
    public function getDownloadUrlAttribute(): string
    {
        return route('attachments.download', $this->id);
    }

    // Accessor for file size (human readable)
    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes === 0)
            return '0 B';
        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 1) . ' ' . $sizes[$i];
    }

}
