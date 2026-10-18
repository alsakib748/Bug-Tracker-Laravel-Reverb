<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Human-friendly labels for permission groups.
     *
     * @var array<string, string>
     */
    private const GROUP_LABELS = [
        'projects' => 'Projects',
        'issues' => 'Issues',
        'comments' => 'Comments',
        'attachments' => 'Attachments',
        'labels' => 'Labels',
        'sprints' => 'Sprints',
        'board' => 'Board',
        'backlog' => 'Backlog',
        'automation' => 'Automation',
        'analytics' => 'Analytics',
        'insights' => 'AI Insights',
        'ai' => 'AI Tools',
        'github' => 'GitHub',
        'users' => 'Users',
        'roles' => 'Roles',
        'activity' => 'Activity',
        'notifications' => 'Notifications',
        'dashboard' => 'Dashboard',
    ];

    /**
     * List roles with their assigned permissions.
     */
    public function index()
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->with('permissions:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn (Role $role) => $this->transform($role));

        return response()->json(['data' => $roles]);
    }

    /**
     * The full permission catalog, grouped by resource, for the RBAC matrix UI.
     */
    public function permissions()
    {
        $this->authorize('viewAny', Role::class);

        $byName = Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->keyBy('name');

        $used = [];
        $groups = [];

        foreach (RolesAndPermissionsSeeder::PERMISSIONS as $key => $names) {
            $items = [];
            foreach ($names as $name) {
                if (! isset($byName[$name])) {
                    continue;
                }

                $items[] = ['id' => $byName[$name]->id, 'name' => $name];
                $used[$name] = true;
            }

            if (! empty($items)) {
                $groups[] = [
                    'key' => $key,
                    'label' => self::GROUP_LABELS[$key] ?? Str::headline($key),
                    'permissions' => $items,
                ];
            }
        }

        $leftover = $byName
            ->reject(fn (Permission $permission) => isset($used[$permission->name]))
            ->map(fn (Permission $permission) => ['id' => $permission->id, 'name' => $permission->name])
            ->values()
            ->all();

        if (! empty($leftover)) {
            $groups[] = ['key' => 'other', 'label' => 'Other', 'permissions' => $leftover];
        }

        return response()->json(['data' => $groups]);
    }

    /**
     * Show a single role.
     */
    public function show(Role $role)
    {
        $this->authorize('view', $role);

        $role->load('permissions:id,name');

        return response()->json(['data' => $this->transform($role)]);
    }

    /**
     * Create a role.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        $role->load('permissions:id,name');

        return response()->json(['data' => $this->transform($role)], 201);
    }

    /**
     * Update a role's name and/or permissions. The Admin role is protected.
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        if ($role->name === User::ROLE_ADMIN) {
            return response()->json(['message' => 'The Admin role cannot be modified.'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (! empty($validated['name']) && $validated['name'] !== $role->name) {
            $role->update(['name' => $validated['name']]);
        }

        if (array_key_exists('permissions', $validated)) {
            $role->syncPermissions($validated['permissions']);
        }

        $role->load('permissions:id,name');

        return response()->json(['data' => $this->transform($role)]);
    }

    /**
     * Delete a role. The Admin role is protected.
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        if ($role->name === User::ROLE_ADMIN) {
            return response()->json(['message' => 'The Admin role cannot be deleted.'], 403);
        }

        if ($role->users()->exists()) {
            return response()->json(['message' => 'Cannot delete a role that is assigned to users.'], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'is_protected' => $role->name === User::ROLE_ADMIN,
            'permissions' => $role->permissions->pluck('name')->values(),
            'users_count' => $role->users()->count(),
        ];
    }
}
