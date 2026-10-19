<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * The issues list is filtered by status/priority/type/severity and by
     * "my issues" (assigned_to). These columns were unindexed, forcing full
     * scans once the table grows. project_id / reporter_id / assigned_to are
     * already covered by their foreign keys.
     */
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->index('status', 'issues_status_index');
            $table->index('priority', 'issues_priority_index');
            $table->index('type', 'issues_type_index');
            $table->index('severity', 'issues_severity_index');
            $table->index(['assigned_to', 'status'], 'issues_assigned_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex('issues_status_index');
            $table->dropIndex('issues_priority_index');
            $table->dropIndex('issues_type_index');
            $table->dropIndex('issues_severity_index');
            $table->dropIndex('issues_assigned_status_index');
        });
    }
};
