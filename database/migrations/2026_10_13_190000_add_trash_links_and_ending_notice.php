<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Three things for the keeping of announcements:
 * a bin (deleted_at: one deleted by mistake can be brought back for thirty days),
 * when its author was told it is about to end (ending_told_at, so the bell rings once),
 * and what the nightly look at its links to files kept elsewhere found (link_checks).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->softDeletes();
            $table->timestamp('ending_told_at')->nullable();
        });

        Schema::create('link_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->text('url');
            $table->char('url_hash', 40);
            // ok, dead (the file is gone), private (not shared with everyone); null until it has been looked at.
            $table->string('state', 10)->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->unique(['announcement_id', 'url_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_checks');

        Schema::table('announcements', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('ending_told_at');
        });
    }
};
