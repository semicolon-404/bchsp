<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - announcements.status: published (as every one was until now), draft (still being written), or pending
 *   (written by the account of a department and waiting for someone who posts for the whole hospital to
 *   approve it). Only a published one is ever shown to the public, whatever its dates.
 * - announcements.pinned_in_department: kept at the top of its department's page. One at a time for each department.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('status', 20)->default('published')->after('slug');
            $table->boolean('pinned_in_department')->default(false)->after('is_pinned');

            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex(['status', 'starts_at']);
            $table->dropColumn(['status', 'pinned_in_department']);
        });
    }
};
