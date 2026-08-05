<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * List users.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'role', 'status', 'sort']);
        $perPage = $request->get('per_page', 15);

        $users = $this->userService->getAllUsers($filters, $perPage);

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        // $this->authorize('create', User::class);

        $user = $this->userService->createUser($request->validated());

        return new UserResource($user);
    }

    /**
     * Show a single user.
     */
    public function show(User $user)
    {
        // $this->authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * Update a user (admin only).
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // $this->authorize('update', $user);

        $updated = $this->userService->updateUser($user, $request->validated());

        return new UserResource($updated);
    }

    /**
     * Delete a user (admin only).
     */
    public function destroy(User $user)
    {
        // $this->authorize('delete', $user);

        try {
            $this->userService->deleteUser($user);
            return response()->json(['message' => 'User deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
