<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get the authenticated user's profile.
     */
    public function show(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $updated = $this->userService->updateProfile($user, $request->validated());

        return new UserResource($updated);
    }

}