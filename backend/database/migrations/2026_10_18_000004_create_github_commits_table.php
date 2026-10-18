<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_commits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('github_repository_id')->constrained('github_repositories')->cascadeOnDelete();
            $table->string('sha', 40);
            $table->text('message_summary')->nullable();
            $table->string('author_login')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->string('html_url')->nullable();
            $table->timestamps();

            $table->unique(['github_repository_id', 'sha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_commits');
    }
};
