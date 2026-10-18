<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GitHubWebhookDelivery extends Model
{
    protected $table = 'github_webhook_deliveries';

    protected $fillable = [
        'github_delivery_id',
        'github_installation_id',
        'github_repository_id',
        'event_type',
        'action',
        'payload_summary',
        'status',
        'attempt_count',
        'received_at',
        'processed_at',
        'error_code',
    ];

    protected $casts = [
        'payload_summary' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
