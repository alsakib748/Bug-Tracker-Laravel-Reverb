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

        $roles = $this->getRoleNames();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $avatar,
            'role' => $roles->first(), // primary (single) role name
            'role_label' => $roles->first(),
            'roles' => $roles,
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'is_admin' => $this->isAdmin(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'last_seen' => $this->last_seen?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}