<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatar = $this->avatar;

        if ($avatar && !preg_match('/^(https?:)?\/\//', $avatar)) {
            $avatar = str_starts_with($avatar, 'storage/')
                ? asset($avatar)
                : asset('storage/' . ltrim($avatar, '/'));
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $avatar,
            'role' => $this->role?->value, // enum value
            'role_label' => $this->role?->label(), // human-readable
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'last_seen' => $this->last_seen?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
