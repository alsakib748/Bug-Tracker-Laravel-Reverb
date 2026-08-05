<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserService
{
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
                $q->where('role', $role);
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
                $q->orderBy($sort, $direction);
            }, fn($q) => $q->orderBy('id', 'desc'));

        return $query->paginate($perPage);
    }

    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'tester',
            'status' => $data['status'] ?? 'active',
            'avatar' => $data['avatar'] ?? null,
        ]);

        return $user;
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
            'role' => $data['role'] ?? $user->role,
            'status' => $data['status'] ?? $user->status,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
        return $user->fresh();
    }

    /**
     * Delete a user (admin).
     */
    public function deleteUser(User $user): void
    {
        // Business rule: prevent deleting the last admin
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
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
            $updateData['password'] = Hash::make($data['password']);
        }

        if (!empty($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $updateData['avatar'] = $data['avatar']->store('avatars', 'public');
        }

        $user->update($updateData);
        return $user->fresh();
    }
}
