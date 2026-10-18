<?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public Issue $issue;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Issue $issue,
        public int $assignedTo,
        public ?int $fromAssigneeId = null,
        public ?string $eventId = null,
        public ?int $actorId = null,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'issue.'.$this->issue->id,
            ),
            new PrivateChannel(
                'user.'.$this->assignedTo
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'issue.assigned';
    }

    public function broadcastWith(): array
    {
        // return [
        //     'id' => $this->issue->id,
        //     'title' => $this->issue->title,
        //     'project_id' => $this->issue->project_id,
        //     'assigned_by' => auth()->user()?->name,
        // ];
        return [
            'issue_id' => $this->issue->id,
            'assigned_to' => $this->assignedTo,
        ];
    }
}
