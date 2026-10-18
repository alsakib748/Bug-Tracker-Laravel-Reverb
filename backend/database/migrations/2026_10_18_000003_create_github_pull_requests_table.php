<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_pull_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('github_repository_id')->constrained('github_repositories')->cascadeOnDelete();
            $table->unsignedBigInteger('github_pr_id');
            $table->integer('number');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('html_url');
            $table->string('state', 20); // open, closed
            $table->boolean('is_merged')->default(false);
            $table->boolean('draft')->default(false);
            $table->string('head_ref')->nullable();
            $table->string('base_ref')->nullable();
            $table->string('head_sha', 40)->nullable();
            $table->string('base_sha', 40)->nullable();
            $table->string('author_login')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('merged_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['github_repository_id', 'github_pr_id']);
            $table->index(['github_repository_id', 'number']);
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_pull_requests');
    }
};
