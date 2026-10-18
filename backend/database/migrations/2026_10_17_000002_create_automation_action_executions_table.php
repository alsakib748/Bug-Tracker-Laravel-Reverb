<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_action_executions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('automation_execution_id')
                ->constrained('automation_executions')
                ->cascadeOnDelete();

            $table->string('action_key', 60);
            $table->string('action_type', 60);
            $table->string('status', 30)->default('pending');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('idempotency_key', 120)->nullable();
            $table->json('input_summary')->nullable();
            $table->json('result_summary')->nullable();
            $table->string('error_code', 100)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['automation_execution_id', 'status'], 'automation_action_exec_status_index');
            $table->unique('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_action_executions');
    }
};
