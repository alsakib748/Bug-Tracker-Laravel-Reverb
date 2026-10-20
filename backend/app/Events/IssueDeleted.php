<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsToProjectAudience;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueDeleted implements ShouldBroadcast
{
    use BroadcastsToProjectAudience, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $issueId,
        public int $projectId,
        public ?string $eventId = null,
        public ?int $actorId = null,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return array_merge(
            [
                new PrivateChannel('issue.'.$this->issueId),
                new PrivateChannel('project.'.$this->projectId),
            ],
            $this->projectAudienceChannels($this->projectId),
        );
    }

    public function broadcastAs(): string
    {
        return 'issue.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'issue_id' => $this->issueId,
            'project_id' => $this->projectId,
        ];
    }
}
