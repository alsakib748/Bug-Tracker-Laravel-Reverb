<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * The canonical permission catalog, grouped by resource.
     *
     * @var array<string, array<int, string>>
     */
    public const PERMISSIONS = [
        'projects' => [
            'view projects',
            'create projects',
            'update projects',
            'delete projects',
            'manage project members',
            'archive projects',
        ],
        'issues' => [
            'view issues',
            'create issues',
            'update issues',
            'delete issues',
            'assign issues',
            'change issue status',
            'close issues',
            'reopen issues',
            'manage issue labels',
            'manage issue watchers',
            'manage issue links',
            'bulk update issues',
        ],
        'comments' => [
            'view comments',
            'create comments',
            'update comments',
            'delete comments',
        ],
        'attachments' => [
            'view attachments',
            'upload attachments',
            'delete attachments',
        ],
        'labels' => [
            'view labels',
            'manage labels',
        ],
        'sprints' => [
            'view sprints',
            'manage sprints',
        ],
        'board' => [
            'view board',
            'manage board',
        ],
        'backlog' => [
            'view backlog',
            'manage backlog',
        ],
        'automation' => [
            'view automation rules',
            'manage automation rules',
        ],
        'analytics' => [
            'view analytics',
        ],
        'insights' => [
            'view insights',
            'generate insights',
        ],
        'ai' => [
            'use ai bug report',
        ],
        'github' => [
            'view github integration',
            'manage github integration',
            'manage github links',
        ],
        'users' => [
            'view users',
            'create users',
            'update users',
            'delete users',
            'assign roles',
        ],
        'roles' => [
            'view roles',
            'manage roles',
        ],
        'activity' => [
            'view activity logs',
        ],
        'notifications' => [
            'view notifications',
            'manage notifications',
        ],
        'dashboard' => [
            'view dashboard',
        ],
    ];

    /**
     * Role names.
     */
    public const ROLES = [
        'Admin',
        'Project Manager',
        'Developer',
        'QA Tester',
        'Reporter',
        'Viewer',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = collect(self::PERMISSIONS)->flatten()->all();

        foreach ($allPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $matrix = $this->matrix();

        foreach (self::ROLES as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($matrix[$roleName] ?? []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Permission matrix for each role.
     *
     * @return array<string, array<int, string>>
     */
    public function matrix(): array
    {
        $all = collect(self::PERMISSIONS)->flatten()->all();

        $projectManager = array_merge(
            self::PERMISSIONS['projects'],
            self::PERMISSIONS['issues'],
            self::PERMISSIONS['comments'],
            self::PERMISSIONS['attachments'],
            self::PERMISSIONS['labels'],
            self::PERMISSIONS['sprints'],
            self::PERMISSIONS['board'],
            self::PERMISSIONS['backlog'],
            self::PERMISSIONS['automation'],
            self::PERMISSIONS['analytics'],
            self::PERMISSIONS['insights'],
            self::PERMISSIONS['ai'],
            self::PERMISSIONS['github'],
            ['view activity logs'],
            self::PERMISSIONS['notifications'],
            self::PERMISSIONS['dashboard'],
        );

        $developer = [
            'view projects',
            'view issues', 'create issues', 'update issues', 'assign issues',
            'change issue status', 'close issues', 'reopen issues',
            'manage issue labels', 'manage issue watchers', 'manage issue links',
            'view comments', 'create comments', 'update comments', 'delete comments',
            'view attachments', 'upload attachments', 'delete attachments',
            'view labels', 'manage labels',
            'view sprints',
            'view board', 'manage board',
            'view backlog', 'manage backlog',
            'view automation rules',
            'view analytics',
            'view insights', 'generate insights',
            'use ai bug report',
            'view github integration', 'manage github links',
            'view activity logs',
            'view notifications', 'manage notifications',
            'view dashboard',
        ];

        $qaTester = [
            'view projects',
            'view issues', 'create issues', 'update issues',
            'change issue status', 'close issues', 'reopen issues',
            'manage issue labels', 'manage issue watchers',
            'view comments', 'create comments', 'update comments', 'delete comments',
            'view attachments', 'upload attachments', 'delete attachments',
            'view labels',
            'view sprints',
            'view board', 'manage board',
            'view backlog',
            'view automation rules',
            'view analytics',
            'view insights',
            'use ai bug report',
            'view github integration',
            'view activity logs',
            'view notifications', 'manage notifications',
            'view dashboard',
        ];

        $reporter = [
            'view projects',
            'view issues', 'create issues',
            'view comments', 'create comments', 'update comments', 'delete comments',
            'view attachments', 'upload attachments', 'delete attachments',
            'view labels',
            'view board',
            'view backlog',
            'view analytics',
            'view insights',
            'view activity logs',
            'view notifications', 'manage notifications',
            'view dashboard',
        ];

        $viewer = [
            'view projects',
            'view issues',
            'view comments',
            'view attachments',
            'view labels',
            'view sprints',
            'view board',
            'view backlog',
            'view analytics',
            'view insights',
            'view activity logs',
            'view notifications',
            'view dashboard',
        ];

        return [
            'Admin' => $all,
            'Project Manager' => array_values(array_unique($projectManager)),
            'Developer' => array_values(array_unique($developer)),
            'QA Tester' => array_values(array_unique($qaTester)),
            'Reporter' => array_values(array_unique($reporter)),
            'Viewer' => array_values(array_unique($viewer)),
        ];
    }
}
