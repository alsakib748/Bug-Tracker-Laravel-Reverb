<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GitHubInstallationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'github_installation_id' => $this->github_installation_id,
            'account_login' => $this->account_login,
            'account_type' => $this->account_type,
            'status' => $this->status,
            'installed_at' => $this->installed_at?->toISOString(),
            'last_verified_at' => $this->last_verified_at?->toISOString(),
            'repositories' => GitHubRepositoryResource::collection($this->whenLoaded('repositories')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
