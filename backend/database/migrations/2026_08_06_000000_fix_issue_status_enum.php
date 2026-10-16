<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `resolve` value was a typo; the application (PHP enum, seeders,
     * queries) uses `resolved`. This repairs databases that were already
     * migrated before the typo was corrected in the create migration.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `issues` MODIFY `status` ENUM('open','assigned','in_progress','code_review','testing','resolved','closed','reopened') NOT NULL DEFAULT 'open'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE issues DROP CONSTRAINT IF EXISTS issues_status_check");
            DB::statement("UPDATE issues SET status = 'resolved' WHERE status = 'resolve'");
            DB::statement("ALTER TABLE issues ADD CONSTRAINT issues_status_check CHECK (status IN ('open','assigned','in_progress','code_review','testing','resolved','closed','reopened'))");
        }
        // SQLite enums are enforced via a check constraint created with the
        // table; a rebuild would be required. Fresh installs already use the
        // corrected create migration, so nothing to do here.
    }

    public function down(): void
    {
        // No-op: the corrected enum values are the intended state.
    }
};
