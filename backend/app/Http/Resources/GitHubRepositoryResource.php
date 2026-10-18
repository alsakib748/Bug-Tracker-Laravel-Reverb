<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GitHubRepositoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'github_repository_id' => $this->github_repository_id,
            'owner_login' => $this->owner_login,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'html_url' => $this->html_url,
            'default_branch' => $this->default_branch,
            'is_private' => $this->is_private,
            'status' => $this->status,
            'last_synced_at' => $this->last_synced_at?->toISOString(),
            'pivot' => $this->whenLoaded('pivot', fn () => [
                'is_default' => (bool) $this->pivot->is_default,
                'linked_by' => $this->pivot->linked_by,
                'linked_at' => $this->pivot->created_at?->toISOString(),
            ]),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
