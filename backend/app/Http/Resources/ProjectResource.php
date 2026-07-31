<?php

namespace App\Http\Resources;

use App\Enums\ProjectStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'color' => $this->color,
            'status' => $this->status?->value ?? ProjectStatus::ACTIVE->value,
            'status_label' => $this->status?->label() ?? ProjectStatus::ACTIVE->label(),
            'created_by' => $this->whenLoaded('creator', fn() => new UserResource($this->creator)),
            'members' => $this->whenLoaded('members', fn() => UserResource::collection($this->members)),
            'issues_count' => $this->whenCounted('issues'),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
