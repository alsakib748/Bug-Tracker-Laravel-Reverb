<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'avatar' => fake()->imageUrl(100, 100, 'people', true, 'avatar'), // placeholder
            'status' => fake()->randomElement(['active', 'inactive']),
            'last_seen' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Assign a Spatie role once the user has been persisted.
     */
    protected function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            $user->assignRole($role);
        });
    }

    // Helpers for specific roles
    public function admin(): static
    {
        return $this->withRole(User::ROLE_ADMIN);
    }

    public function projectManager(): static
    {
        return $this->withRole(User::ROLE_PROJECT_MANAGER);
    }

    public function developer(): static
    {
        return $this->withRole(User::ROLE_DEVELOPER);
    }

    public function tester(): static
    {
        return $this->withRole(User::ROLE_QA_TESTER);
    }

    public function reporter(): static
    {
        return $this->withRole(User::ROLE_REPORTER);
    }

    public function viewer(): static
    {
        return $this->withRole(User::ROLE_VIEWER);
    }
}
