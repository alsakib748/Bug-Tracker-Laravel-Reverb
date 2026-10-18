<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public Comment $comment;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Comment $comment,
        public ?string $eventId = null,
        public ?int $actorId = null,
    ) {
        $this->comment->load('user');
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
                'issue.'.$this->comment->issue_id
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }

    public function broadcastWith(): array
    {
        // return [
        //     'id' => $this->comment->id,
        //     'comment' => $this->comment->comment,
        //     'user_id' => $this->comment->user_id,
        //     'user_name' => $this->comment->user->name,
        //     'created_at' => $this->comment->created_at->toISOString(),
        // ];
        return [
            'comment' => $this->comment->toArray(),
        ];
    }
}
