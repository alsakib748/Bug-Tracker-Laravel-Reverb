<?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class BoardIssueMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly Issue $issue,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly int $position,
        public readonly ?string $actorName = null,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('project.'.$this->issue->project_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'issue.board.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'issue_id' => $this->issue->id,
            'project_id' => $this->issue->project_id,
            'from_status' => $this->fromStatus,
            'to_status' => $this->toStatus,
            'position' => $this->position,
            'version' => $this->issue->version,
            'actor_name' => $this->actorName,
            'moved_at' => now()->toISOString(),
        ];
    }
}
