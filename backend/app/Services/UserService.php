<?php

namespace App\Services;

use App\Models\User;
use App\Support\AvatarOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(private AvatarOptimizer $avatars = new AvatarOptimizer()) {}

    /**
     * Get all users with optional filters and pagination.
     */
    public function getAllUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, function ($q, $role) {
                $q->whereHas('roles', function ($roleQuery) use ($role) {
                    $roleQuery->where('name', $role);
                });
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['sort'] ?? null, function ($q, $sort) {
                $direction = 'asc';
                if (str_starts_with($sort, '-')) {
                    $direction = 'desc';
                    $sort = substr($sort, 1);
                }
                $allowed = ['id', 'name', 'email', 'status', 'created_at'];
                if (in_array($sort, $allowed, true)) {
                    $q->orderBy($sort, $direction);
                }
            }, fn($q) => $q->orderBy('id', 'desc'));

        return $query->paginate($perPage);
    }

    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => $data['status'] ?? 'active',
            'avatar' => $data['avatar'] ?? null,
        ]);

        $user->assignRole($data['role'] ?? User::ROLE_REPORTER);

        return $user->fresh('roles');
    }

    /**
     * Get a single user by ID.
     */
    public function getUserById(int $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Update a user (admin).
     */
    public function updateUser(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'status' => $data['status'] ?? $user->status,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        $user->update($updateData);

        if (!empty($data['role'])) {
            $this->assignRoles($user, [$data['role']]);
        }

        return $user->fresh('roles');
    }

    /**
     * Sync the user's role (single role per user).
     *
     * @param  array<int, string>  $roles
     */
    public function assignRoles(User $user, array $roles): User
    {
        $roles = array_values(array_filter($roles));

        // Business rule: never leave the system without an administrator.
        if ($user->isAdmin() && ! in_array(User::ROLE_ADMIN, $roles, true)) {
            if (User::role(User::ROLE_ADMIN)->count() <= 1) {
                throw new \Exception('Cannot remove the administrator role from the last admin user.');
            }
        }

        $user->syncRoles($roles);

        return $user->fresh('roles');
    }

    /**
     * Delete a user (admin).
     */
    public function deleteUser(User $user): void
    {
        // Business rule: prevent deleting the last admin
        if ($user->isAdmin() && User::role(User::ROLE_ADMIN)->count() <= 1) {
            throw new \Exception('Cannot delete the last admin user.');
        }
        $user->delete();
    }

    /**
     * Update authenticated user's profile.
     */
    public function updateProfile(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        if (!empty($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $updateData['avatar'] = $this->avatars->storeUploaded($data['avatar']);
        }

        $user->update($updateData);
        return $user->fresh();
    }
}
