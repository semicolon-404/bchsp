<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Once, as the new site goes live: the visit counts start from nothing, as on a new site. The visits by day, the
 * words searched for, and how often each announcement was opened and found useful. Nothing else is touched: the
 * announcements, the staff's activity and the access log stay as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_visits')->delete();

        if (Schema::hasTable('search_terms')) {
            DB::table('search_terms')->delete();
        }

        DB::table('announcements')->update(['views' => 0, 'helpful' => 0]);

        Cache::forget('visits:totals');
    }

    public function down(): void
    {
        // What was counted is gone.
    }
};
