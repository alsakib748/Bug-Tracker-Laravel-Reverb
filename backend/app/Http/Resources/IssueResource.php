<?php

namespace App\Http\Resources;

use App\Http\Resources\AttachmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority?->value,
            'priority_label' => $this->priority?->label(),
            'priority_color' => $this->priority?->color(),
            'severity' => $this->severity?->value,
            'severity_label' => $this->severity?->label(),
            'severity_color' => $this->severity?->color(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'type_icon' => $this->type?->icon(),
            'due_date' => $this->due_date?->toISOString(),
            'estimated_hours' => $this->estimated_hours,
            'completed_at' => $this->completed_at?->toISOString(),
            'project' => $this->whenLoaded('project', fn() => new ProjectResource($this->project)),
            'reporter' => $this->whenLoaded('reporter', fn() => new UserResource($this->reporter)),
            'assignee' => $this->whenLoaded('assignee', fn() => new UserResource($this->assignee)),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->whenCounted('comments'),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'attachments_count' => $this->whenCounted('attachments'),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}