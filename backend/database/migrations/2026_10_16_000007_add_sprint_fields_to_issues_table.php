<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            if (! Schema::hasColumn('issues', 'sprint_id')) {
                $table->foreignId('sprint_id')
                    ->nullable()
                    ->after('project_id')
                    ->constrained('sprints')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('issues', 'story_points')) {
                $table->unsignedSmallInteger('story_points')->nullable()->after('estimated_hours');
            }

            if (! Schema::hasColumn('issues', 'backlog_position')) {
                $table->unsignedBigInteger('backlog_position')->nullable()->after('story_points');
            }

            if (! Schema::hasColumn('issues', 'board_position')) {
                $table->unsignedBigInteger('board_position')->nullable()->after('backlog_position');
            }

            if (! Schema::hasColumn('issues', 'version')) {
                $table->unsignedInteger('version')->default(0)->after('board_position');
            }
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->index(['project_id', 'sprint_id'], 'issues_project_sprint_index');
            $table->index(['project_id', 'status', 'board_position'], 'issues_project_status_board_index');
            $table->index(['project_id', 'backlog_position'], 'issues_project_backlog_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndex('issues_project_sprint_index');
            $table->dropIndex('issues_project_status_board_index');
            $table->dropIndex('issues_project_backlog_index');
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['sprint_id']);
            $table->dropColumn([
                'sprint_id',
                'story_points',
                'backlog_position',
                'board_position',
                'version',
            ]);
        });
    }
};
