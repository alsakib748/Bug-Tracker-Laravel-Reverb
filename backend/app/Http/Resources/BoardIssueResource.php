<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardIssueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'issue_key' => $this->issue_key ?? ('ISS-'.$this->id),
            'title' => $this->title,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'priority' => $this->priority?->value,
            'priority_label' => $this->priority?->label(),
            'priority_color' => $this->priority?->color(),
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'type_icon' => $this->type?->icon(),
            'sprint_id' => $this->sprint_id,
            'story_points' => $this->story_points,
            'board_position' => $this->board_position,
            'backlog_position' => $this->backlog_position,
            'version' => $this->version,
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'avatar' => $this->assetAvatar($this->assignee->avatar),
            ] : null),
            'labels' => $this->whenLoaded('labels', fn () => $this->labels->map(fn ($label) => [
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Turn a stored avatar path ("avatars/x.png") into a public URL.
     * Absolute URLs (external avatars) are returned untouched.
     */
    protected function assetAvatar(?string $avatar): ?string
    {
        if (!$avatar || preg_match('/^(https?:)?\/\//', $avatar)) {
            return $avatar;
        }

        return str_starts_with($avatar, 'storage/')
            ? asset($avatar)
            : asset('storage/' . ltrim($avatar, '/'));
    }
}
