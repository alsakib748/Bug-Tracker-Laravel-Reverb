<?php

namespace App\Events;

use App\Models\IssueLink;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueLinkCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public IssueLink $link,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('issue.' . $this->link->source_issue_id),
            new PrivateChannel('issue.' . $this->link->target_issue_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'issue.link.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->link->id,
            'source_issue_id' => $this->link->source_issue_id,
            'target_issue_id' => $this->link->target_issue_id,
            'link_type' => $this->link->link_type?->value,
        ];
    }
}
