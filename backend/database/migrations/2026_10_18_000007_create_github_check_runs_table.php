<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_check_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('github_repository_id')->constrained('github_repositories')->cascadeOnDelete();
            $table->unsignedBigInteger('github_check_run_id');
            $table->string('name');
            $table->string('head_sha', 40);
            $table->string('status', 20);
            $table->string('conclusion', 30)->nullable();
            $table->string('html_url')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Explicit short name: auto-generated name exceeds MySQL's 64-char limit.
            $table->unique(['github_repository_id', 'github_check_run_id'], 'github_check_run_unique');
            $table->index(['head_sha', 'status'], 'github_check_runs_sha_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_check_runs');
    }
};
