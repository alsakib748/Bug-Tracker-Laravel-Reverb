<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_bug_report_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('queued');

            $table->json('input_snapshot')->nullable();
            $table->string('input_hash', 64)->nullable();

            $table->json('suggestions')->nullable();
            $table->json('review_decisions')->nullable();
            $table->json('applied_fields')->nullable();

            $table->string('provider', 100)->nullable();
            $table->string('model', 150)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();

            $table->string('error_code', 100)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['requested_by', 'status']);
            $table->index(['issue_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_bug_report_suggestions');
    }
};
