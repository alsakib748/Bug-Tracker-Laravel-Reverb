<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Add new columns
            $table->string('entity_type')->nullable()->after('action');
            $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type');
            $table->json('properties')->nullable()->after('description');
            $table->string('user_agent')->nullable()->after('ip');
            // Add indexes for commonly queried columns
            $table->index('entity_type');
            $table->index('entity_id');
            $table->index(['user_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
            $table->index(['issue_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            // Drop the columns
            $table->dropColumn([
                'entity_type',
                'entity_id',
                'properties',
                'user_agent',
            ]);

            // Drop the indexes (optional, but good practice)
            $table->dropIndex(['entity_type']);
            $table->dropIndex(['entity_id']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['project_id', 'created_at']);
            $table->dropIndex(['issue_id', 'created_at']);
            $table->dropIndex(['action']);
        });
    }
};
