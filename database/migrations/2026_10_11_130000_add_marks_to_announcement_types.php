<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each kind of announcement gets a mark of its own, a symbol in a colour, so that a
 * reader running an eye down the list knows a procurement notice from a job opening
 * before reading a word (App\Models\AnnouncementType).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcement_types', function (Blueprint $table) {
            $table->string('icon', 40)->default('megaphone')->after('slug');
            $table->string('tone', 20)->default('accent')->after('icon');
        });

        // The three kinds a new site starts with.
        foreach (['news' => ['megaphone', 'accent'], 'procurement' => ['clipboard-document-list', 'yellow'], 'jobs' => ['briefcase', 'purple']] as $slug => [$icon, $tone]) {
            DB::table('announcement_types')->where('slug', $slug)->update(['icon' => $icon, 'tone' => $tone]);
        }
    }

    public function down(): void
    {
        Schema::table('announcement_types', function (Blueprint $table) {
            $table->dropColumn(['icon', 'tone']);
        });
    }
};
