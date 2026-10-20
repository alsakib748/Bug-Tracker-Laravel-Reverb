<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes justified by EXPLAIN on the hot read paths:
     *
     * - issues.created_at: global issue lists sorted by created_at and
     *   cross-project "created in period" analytics ranges (the existing
     *   (project_id, created_at) composite cannot serve an unscoped range).
     * - activity_logs.created_at: the unfiltered admin activity feed orders by
     *   created_at with no project/user/issue predicate.
     * - issue_status_histories.created_at: period-scoped time-in-status
     *   reports filter on the transition timestamp alone.
     */
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->index('created_at', 'issues_created_at_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('created_at', 'activity_logs_created_at_index');
        });

        Schema::table('issue_status_histories', function (Blueprint $table) {
            $table->index('created_at', 'issue_status_histories_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex('issues_created_at_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_created_at_index');
        });

        Schema::table('issue_status_histories', function (Blueprint $table) {
            $table->dropIndex('issue_status_histories_created_at_index');
        });
    }
};
