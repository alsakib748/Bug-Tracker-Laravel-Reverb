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
        Schema::create('issue_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('target_issue_id')->constrained('issues')->cascadeOnDelete();
            $table->string('link_type', 30);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['source_issue_id', 'target_issue_id', 'link_type']);
            $table->index('target_issue_id');
            $table->index('link_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_links');
    }
};
