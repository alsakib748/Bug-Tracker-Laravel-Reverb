<?php

namespace App\Events;

use App\Http\Resources\IssueResource;
use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
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
    public function __construct(
        public Issue $issue,
        public array $before = [],
        public ?string $eventId = null,
        public ?int $actorId = null,
    ) {
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
            // Detail view listens on the issue channel; lists and boards listen
            // on the project channel so they also observe edits.
            new PrivateChannel(
                'issue.'.$this->issue->id
            ),
            new PrivateChannel(
                'project.'.$this->issue->project_id
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'issue.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'issue' => (new IssueResource($this->issue))->resolve(),
        ];
    }
}
