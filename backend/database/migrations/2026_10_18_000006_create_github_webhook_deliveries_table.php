<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('github_delivery_id')->unique();
            $table->unsignedBigInteger('github_installation_id')->nullable();
            $table->unsignedBigInteger('github_repository_id')->nullable();
            $table->string('event_type', 60);
            $table->string('action', 60)->nullable();
            $table->json('payload_summary')->nullable();
            $table->string('status', 30)->default('received'); // received, queued, processed, ignored, failed
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamps();

            $table->index(['event_type', 'status']);
            $table->index('github_repository_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_webhook_deliveries');
    }
};
