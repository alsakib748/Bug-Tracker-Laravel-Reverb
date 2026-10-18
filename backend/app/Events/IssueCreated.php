<?php

namespace App\Events;

use App\Http\Resources\IssueResource;
use App\Models\Issue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public Issue $issue;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Issue $issue,
        public ?string $eventId = null,
        public ?int $actorId = null,
    ) {
        $this->issue->load(['project', 'reporter', 'assignee']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'project.'.$this->issue->project_id
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'issue.created';
    }

    public function broadcastWith(): array
    {
        // Mirror the REST payload so clients can reuse the same rendering code
        // and never receive internal attributes.
        return [
            'issue' => (new IssueResource($this->issue))->resolve(),
        ];
    }
}
