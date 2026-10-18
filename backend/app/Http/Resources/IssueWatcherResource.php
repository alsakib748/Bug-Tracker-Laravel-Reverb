<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueWatcherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatar = $this->avatar;

        if ($avatar && ! preg_match('/^(https?:)?\/\//', $avatar)) {
            $avatar = str_starts_with($avatar, 'storage/')
                ? asset($avatar)
                : asset('storage/' . ltrim($avatar, '/'));
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $avatar,
            'role' => $this->getRoleNames()->first(),
            'watched_at' => $this->pivot?->created_at?->toISOString(),
        ];
    }
}
