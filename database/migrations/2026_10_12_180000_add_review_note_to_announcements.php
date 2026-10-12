<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * announcements.review_note: what the reviewer wrote when sending a department's post back for changes
 * (status "returned"). The department reads it on the post, changes it and sends it again; it is cleared
 * when the post is approved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->text('review_note')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', fn (Blueprint $table) => $table->dropColumn('review_note'));
    }
};
