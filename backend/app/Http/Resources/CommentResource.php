<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatar = $this->user->avatar ?? null;

        if ($avatar && !preg_match('/^(https?:)?\/\//', $avatar)) {
            $avatar = str_starts_with($avatar, 'storage/')
                ? asset($avatar)
                : asset('storage/' . ltrim($avatar, '/'));
        }

        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'user_id' => $this->user_id,
            'user_name' => $this->user->name ?? null,
            'user_avatar' => $avatar,
        ];
    }
}
