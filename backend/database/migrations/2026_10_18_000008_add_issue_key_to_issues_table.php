<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->string('issue_key', 30)->nullable()->unique()->after('id');
        });

        // Backfill existing issues with the canonical ISS-{id} key.
        DB::table('issues')->whereNull('issue_key')->orderBy('id')
            ->each(function (object $issue): void {
                DB::table('issues')->where('id', $issue->id)->update([
                    'issue_key' => 'ISS-' . $issue->id,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropUnique(['issue_key']);
            $table->dropColumn('issue_key');
        });
    }
};
