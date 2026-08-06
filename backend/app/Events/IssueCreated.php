<?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Issue $issue;

    /**
     * Create a new event instance.
     */
    public function __construct(Issue $issue)
    {
        $this->issue = $issue;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): Channel
    {
        return new Channel('private-project.' . $this->issue->project_id);
    }

    public function broadcastAs(): string
    {
        return 'issue.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->issue->id,
            'title' => $this->issue->title,
            'status' => $this->issue->status,
            'priority' => $this->issue->priority,
            'project_id' => $this->issue->project_id,
            'reporter_id' => $this->issue->reporter_id,
            'assigned_to' => $this->issue->assigned_to,
            'created_at' => $this->issue->created_at->toISOString(),
        ];
    }

}
