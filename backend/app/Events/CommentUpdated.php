<?php

namespace App\Events;

use App\Http\Resources\CommentResource;
use App\Models\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Comment $comment,
        public ?int $actorId = null,
    ) {
        $this->comment->load('user');
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('issue.'.$this->comment->issue_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'comment' => (new CommentResource($this->comment))->resolve(),
        ];
    }
}
