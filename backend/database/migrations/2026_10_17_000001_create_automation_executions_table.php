<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_executions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('automation_rule_id')
                ->constrained('automation_rules')
                ->cascadeOnDelete();

            $table->unsignedInteger('rule_version');
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            $table->string('event_id', 100);
            $table->string('event_type', 60);

            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->string('status', 30)->default('queued');
            $table->json('matched_conditions')->nullable();
            $table->string('error_code', 100)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['automation_rule_id', 'status']);
            $table->index(['project_id', 'created_at']);
            $table->unique(
                ['automation_rule_id', 'event_id', 'rule_version'],
                'automation_executions_dedup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_executions');
    }
};
