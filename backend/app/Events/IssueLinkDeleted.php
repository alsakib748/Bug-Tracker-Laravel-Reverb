<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueLinkDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $sourceIssueId,
        public int $targetIssueId,
        public int $linkId,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('issue.' . $this->sourceIssueId),
            new PrivateChannel('issue.' . $this->targetIssueId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'issue.link.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->linkId,
            'source_issue_id' => $this->sourceIssueId,
            'target_issue_id' => $this->targetIssueId,
        ];
    }
}
