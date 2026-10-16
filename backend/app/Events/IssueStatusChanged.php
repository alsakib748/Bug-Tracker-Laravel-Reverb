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

    // public Issue $issue;
    // public string $oldStatus;
    // public string $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Issue $issue,
        public string $oldStatus,
        public string $newStatus,
        public ?int $historyId = null,
        public ?string $actorName = null,
    ) {
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
            ),

            new PrivateChannel(
                'project.' . $this->issue->project_id
            ),
        ];
    }

    public function broadcastAs(): string
    {
        // return 'issue.status_changed';
        return 'issue.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'issue_id' => $this->issue->id,
            'history_id' => $this->historyId,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'status_label' => $this->issue->status?->label(),
            'actor_name' => $this->actorName,
            'assignee' => $this->issue->assignee?->name,
            'changed_at' => now()->toISOString(),
        ];
    }

}
