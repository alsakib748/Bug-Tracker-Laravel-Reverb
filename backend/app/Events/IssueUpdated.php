<?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public Issue $issue;

    /**
     * Create a new event instance.
     */
    public function __construct(public Issue $issue)
    {
        $this->issue->load([
            'project',
            'reporter',
            'assignee',
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'issue.' . $this->issue->id
            )
        ];
    }

    public function broadcastAs(): string
    {
        return 'issue.updated';
    }

    public function broadcastWith(): array
    {
        // return [
        //     'id' => $this->issue->id,
        //     'title' => $this->issue->title,
        //     'status' => $this->issue->status,
        //     'priority' => $this->issue->priority,
        //     'assignee' => $this->issue->assignee?->name,
        //     'updated_at' => $this->issue->updated_at->toISOString(),
        // ];

        return [
            'issue' => $this->issue->toArray(),
        ];

    }
}