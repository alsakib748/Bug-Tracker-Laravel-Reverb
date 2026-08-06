<?php

namespace App\Events;

use App\Models\Attachment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttachmentUploaded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Attachment $attachment;

    /**
     * Create a new event instance.
     */
    public function __construct(Attachment $attachment)
    {
        $this->attachment = $attachment;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): Channel
    {
        return new Channel('private-issue.' . $this->attachment->issue_id);
    }

    public function broadcastAs(): string
    {
        return 'attachment.uploaded';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->attachment->id,
            'file_name' => $this->attachment->file_name,
            'file_size' => $this->attachment->file_size,
            'user_id' => $this->attachment->user_id,
            'created_at' => $this->attachment->created_at->toISOString(),
        ];
    }

}
