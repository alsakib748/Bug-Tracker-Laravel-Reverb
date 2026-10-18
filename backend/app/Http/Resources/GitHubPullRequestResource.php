<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GitHubPullRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'title' => $this->title,
            'html_url' => $this->html_url,
            'state' => $this->state,
            'is_merged' => $this->is_merged,
            'draft' => $this->draft,
            'head_ref' => $this->head_ref,
            'base_ref' => $this->base_ref,
            'author_login' => $this->author_login,
            'opened_at' => $this->opened_at?->toISOString(),
            'merged_at' => $this->merged_at?->toISOString(),
            'repository' => $this->whenLoaded('repository', fn () => new GitHubRepositoryResource($this->repository)),
            'last_synced_at' => $this->last_synced_at?->toISOString(),
        ];
    }
}
