<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
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
            'file_name' => $this->file_name,          // your column name
            'file_size' => $this->file_size,          // your column name
            'file_size_formatted' => $this->file_size_formatted,
            'mime_type' => $this->mime_type,
            'created_at' => $this->created_at->toISOString(),
            'download_url' => $this->download_url,       // from accessor
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
