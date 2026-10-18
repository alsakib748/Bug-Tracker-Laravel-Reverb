<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_insights', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('insight_type', 60); // project_summary, trend_analysis, bottleneck, sprint_review
            $table->date('period_start');
            $table->date('period_end');

            $table->string('status', 30)->default('queued');
            // queued, running, completed, failed

            $table->json('filters')->nullable();
            $table->json('metrics_snapshot')->nullable();
            $table->json('result_json')->nullable();

            $table->string('model_provider', 60)->nullable();
            $table->string('model_name', 120)->nullable();
            $table->string('prompt_version', 30)->default('v1');

            $table->string('error_code', 100)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'insight_type', 'created_at']);
            $table->index(['requested_by', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_insights');
    }
};
