<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_repositories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('github_repository_id')->unique();
            $table->foreignId('github_installation_id')->constrained('github_installations')->cascadeOnDelete();
            $table->string('owner_login');
            $table->string('name');
            $table->string('full_name');
            $table->string('html_url');
            $table->string('default_branch')->nullable();
            $table->boolean('is_private')->default(false);
            $table->string('status', 30)->default('active'); // active, unavailable, archived, disconnected
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_repositories');
    }
};
