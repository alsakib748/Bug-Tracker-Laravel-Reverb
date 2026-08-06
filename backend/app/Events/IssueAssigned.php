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

class IssueAssigned implements ShouldBroadcast
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
        return new Channel('private-user.' . $this->issue->assigned_to);
    }

    public function broadcastAs(): string
    {
        return 'issue.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->issue->id,
            'title' => $this->issue->title,
            'project_id' => $this->issue->project_id,
            'assigned_by' => auth()->user()?->name,
        ];
    }

}
