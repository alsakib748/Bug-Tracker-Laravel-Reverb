<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $mimeTypes = ['image/png', 'image/jpeg', 'application/pdf', 'text/plain'];
        $mime = fake()->randomElement($mimeTypes);
        $extension = explode('/', $mime)[1];

        return [
            'issue_id' => Issue::factory(),
            'user_id' => User::factory(),
            'file_name' => fake()->word() . '.' . $extension,
            'file_path' => 'uploads/attachments/' . fake()->uuid() . '.' . $extension,
            'file_size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'mime_type' => $mime,
        ];
    }
}