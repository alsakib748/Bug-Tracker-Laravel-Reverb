<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_issue_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('github_repository_id')->constrained('github_repositories')->cascadeOnDelete();
            $table->string('entity_type', 30); // pull_request, commit, branch
            $table->unsignedBigInteger('github_entity_id')->nullable();
            $table->integer('external_number')->nullable();
            $table->string('external_sha', 40)->nullable();
            $table->string('external_url');
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('link_source', 30)->default('manual'); // manual, branch, commit_message, pr_description
            $table->timestamps();

            $table->index(['issue_id', 'entity_type']);
            $table->index(['github_repository_id', 'github_entity_id']);
            $table->unique(['issue_id', 'entity_type', 'external_number'], 'github_link_pr_unique');
            $table->unique(['issue_id', 'entity_type', 'external_sha'], 'github_link_commit_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_issue_links');
    }
};
