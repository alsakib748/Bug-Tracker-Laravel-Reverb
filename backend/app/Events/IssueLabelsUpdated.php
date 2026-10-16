<?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueLabelsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, int>  $labelIds
     */
    public function __construct(
        public Issue $issue,
        public array $labelIds,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('issue.' . $this->issue->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'issue.labels.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'issue_id' => $this->issue->id,
            'label_ids' => $this->labelIds,
        ];
    }
}
