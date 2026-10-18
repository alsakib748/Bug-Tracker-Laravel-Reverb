<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mirror the SPA origin so the stateful Sanctum middleware (session +
        // cookie handling) applies during these tests.
        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeaders([
            'Referer' => 'http://localhost:5173/',
            'Origin' => 'http://localhost:5173',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->developer()->create([
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.email', 'jane@example.com');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401);

        $this->assertGuest();
    }

    public function test_registration_creates_a_user(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('data.email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'name' => 'New Person',
        ]);

        // Self-registered users are assigned the Reporter role.
        $this->assertTrue(
            User::where('email', 'new@example.com')->first()->hasRole('Reporter')
        );
    }

    public function test_registration_requires_a_valid_payload(): void
    {
        $this->postJson('/api/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => '123',
        ])->assertStatus(422);
    }

    public function test_protected_routes_reject_unauthenticated_requests(): void
    {
        $this->getJson('/api/issues')->assertStatus(401);
        $this->getJson('/api/projects')->assertStatus(401);
        $this->getJson('/api/users')->assertStatus(401);
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'password',
        ])->assertOk();

        $this->postJson('/api/logout')->assertOk();

        // Guard instances are cached for the lifetime of the test process,
        // unlike a real request lifecycle; reset them before asserting.
        $this->app['auth']->forgetGuards();

        $this->assertGuest();
    }
}
