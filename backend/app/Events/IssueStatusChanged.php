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

class IssueStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Issue $issue;
    public string $oldStatus;
    public string $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Issue $issue, string $oldStatus, string $newStatus)
    {
        $this->issue = $issue;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): Channel
    {
        return new Channel('private-issue.' . $this->issue->id);
    }

    public function broadcastAs(): string
    {
        return 'issue.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->issue->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'assignee' => $this->issue->assignee?->name,
        ];
    }

}
