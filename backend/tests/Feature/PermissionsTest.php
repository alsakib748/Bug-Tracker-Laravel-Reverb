<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_and_permissions_catalog_is_seeded(): void
    {
        $this->assertSame(6, Role::count());
        $this->assertTrue(Permission::count() > 0);
        $this->assertTrue(Role::where('name', 'Admin')->first()->hasAllPermissions(Permission::all()));
    }

    public function test_permission_matrix_and_admin_bypass(): void
    {
        $viewer = User::factory()->viewer()->create();
        $this->assertTrue($viewer->can('view projects'));
        $this->assertFalse($viewer->can('create projects'));
        $this->assertFalse($viewer->can('delete issues'));

        $developer = User::factory()->developer()->create();
        $this->assertTrue($developer->can('update issues'));
        $this->assertFalse($developer->can('manage project members'));

        // Admin bypasses every ability via Gate::before, even undefined ones.
        $admin = User::factory()->admin()->create();
        $this->assertTrue($admin->can('create projects'));
        $this->assertTrue($admin->can('some.undefined.ability'));
    }

    public function test_me_permissions_endpoint_returns_roles_permissions_and_admin_flag(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/me/permissions');

        $response->assertOk()
            ->assertJsonPath('is_admin', true)
            ->assertJsonPath('roles.0', 'Admin');

        $this->assertContains('manage roles', $response->json('permissions'));
    }

    public function test_role_endpoints_are_permission_gated(): void
    {
        $developer = User::factory()->developer()->create();
        Sanctum::actingAs($developer);

        $this->getJson('/api/roles')->assertStatus(403);
    }

    public function test_admin_can_list_roles(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/roles')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Admin')
            ->assertJsonPath('data.0.is_protected', true);
    }

    public function test_permission_catalog_is_grouped_for_the_matrix_ui(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/roles/permissions')->assertOk();

        $labels = collect($response->json('data'))->pluck('label')->all();
        $this->assertContains('Projects', $labels);
        $this->assertContains('Issues', $labels);
        $this->assertGreaterThan(0, collect($response->json('data'))->sum(fn ($group) => count($group['permissions'])));
    }

    public function test_admin_role_cannot_be_modified_or_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $adminRole = Role::where('name', 'Admin')->first();

        $this->putJson("/api/roles/{$adminRole->id}", ['permissions' => []])
            ->assertStatus(403);

        $this->deleteJson("/api/roles/{$adminRole->id}")
            ->assertStatus(403);
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson("/api/users/{$admin->id}/roles", ['roles' => ['Viewer']])
            ->assertStatus(422);
    }

    public function test_admin_can_assign_roles_to_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->viewer()->create();
        Sanctum::actingAs($admin);

        $this->postJson("/api/users/{$user->id}/roles", ['roles' => ['Developer']])
            ->assertOk();

        $this->assertTrue($user->fresh()->hasRole('Developer'));
    }
}
