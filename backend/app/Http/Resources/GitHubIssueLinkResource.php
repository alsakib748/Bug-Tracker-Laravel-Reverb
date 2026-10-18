<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GitHubIssueLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $entityType = $this->entity_type;

        return [
            'id' => $this->id,
            'issue_id' => $this->issue_id,
            'entity_type' => $entityType?->value,
            'entity_type_label' => $entityType?->label(),
            'entity_icon' => $entityType?->icon(),
            'external_number' => $this->external_number,
            'external_sha' => $this->external_sha,
            'external_sha_short' => $this->external_sha ? substr($this->external_sha, 0, 7) : null,
            'external_url' => $this->external_url,
            'link_source' => $this->link_source?->value,
            'link_source_label' => $this->link_source?->label(),
            'repository' => $this->whenLoaded('repository', fn () => new GitHubRepositoryResource($this->repository)),
            'pull_request' => $this->whenLoaded('pullRequest', fn () => [
                'id' => $this->pullRequest->id,
                'number' => $this->pullRequest->number,
                'title' => $this->pullRequest->title,
                'state' => $this->pullRequest->state,
                'is_merged' => $this->pullRequest->is_merged,
                'draft' => $this->pullRequest->draft,
                'head_ref' => $this->pullRequest->head_ref,
                'head_sha' => $this->pullRequest->head_sha,
            ]),
            'ci' => $this->whenLoaded('latestCheckRun', fn () => [
                'name' => $this->latestCheckRun->name,
                'status' => $this->latestCheckRun->status,
                'conclusion' => $this->latestCheckRun->conclusion,
                'html_url' => $this->latestCheckRun->html_url,
            ]),
            'linked_by' => $this->whenLoaded('linkedByUser', fn () => [
                'id' => $this->linkedByUser->id,
                'name' => $this->linkedByUser->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
