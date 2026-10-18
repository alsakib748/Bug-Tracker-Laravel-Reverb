<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectMemberAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $projectId,
        public string $projectName,
        public int $userId,
        public string $userName,
        public string $role,
        public ?int $actorId = null,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('project.'.$this->projectId),
            new PrivateChannel('user.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'project.member.added';
    }

    public function broadcastWith(): array
    {
        return [
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'role' => $this->role,
        ];
    }
}
