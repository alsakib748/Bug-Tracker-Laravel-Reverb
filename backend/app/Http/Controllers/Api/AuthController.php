<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // Self-registered users start as Reporters.
        if (Role::where('name', User::ROLE_REPORTER)->exists()) {
            $user->assignRole(User::ROLE_REPORTER);
        }

        Auth::login($user);

        return new UserResource($user);
    }

    public function login(Request $request)
    {

        // dd($request);

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged in successfully',
            'data' => new UserResource(Auth::user()),
            'status_code' => 200
        ], 200);

    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Return the authenticated user's roles and effective permissions.
     */
    public function permissions(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'is_admin' => $user->isAdmin(),
        ]);
    }

}