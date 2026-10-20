<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Indexes backing the hot analytics/dashboard/notifications queries.
     *
     * issues:
     *  - resolved_at            -> "resolved in period" predicates (dashboard summary,
     *                              per-project breakdown, resolution metrics)
     *  - (project_id, created_at) -> per-project "created in period" date scans;
     *                              the FK index on project_id alone cannot serve
     *                              the created_at range without a filesort
     *  - sprint_id              -> sprint board/backlog filters that scope by
     *                              sprint alone (existing composite is
     *                              (project_id, sprint_id), unusable for that)
     *  - due_date               -> overdue/ upcoming-due filters
     *
     * notifications:
     *  - (notifiable_*, read_at)   -> unread counts and mark-as-read lookups
     *  - (notifiable_*, created_at) -> latest() pagination per notifiable
     *    (the plain morphs() index only covers the two notifiable columns)
     *
     * github_pull_requests:
     *  - opened_at / merged_at  -> GitHub analytics date-window aggregates
     */
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->index('resolved_at', 'issues_resolved_at_index');
            $table->index(['project_id', 'created_at'], 'issues_project_created_at_index');
            $table->index('sprint_id', 'issues_sprint_id_index');
            $table->index('due_date', 'issues_due_date_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_at_index');
            $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_notifiable_created_at_index');
        });

        Schema::table('github_pull_requests', function (Blueprint $table) {
            $table->index('opened_at', 'github_pull_requests_opened_at_index');
            $table->index('merged_at', 'github_pull_requests_merged_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex('issues_resolved_at_index');
            $table->dropIndex('issues_project_created_at_index');
            $table->dropIndex('issues_sprint_id_index');
            $table->dropIndex('issues_due_date_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_at_index');
            $table->dropIndex('notifications_notifiable_created_at_index');
        });

        Schema::table('github_pull_requests', function (Blueprint $table) {
            $table->dropIndex('github_pull_requests_opened_at_index');
            $table->dropIndex('github_pull_requests_merged_at_index');
        });
    }
};
