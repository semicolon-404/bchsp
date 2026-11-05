<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Once, as the new site goes live, at the hospital's asking: of each kind of announcement only the newest is kept
 * on the site (one of those with no kind as well). The rest go to the bin, not away: an administrator can bring any
 * of them back from the panel's bin within thirty days, after which the daily work empties it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $keep = DB::table('announcements')
            ->whereNull('deleted_at')
            ->orderByDesc('starts_at')->orderByDesc('id')
            ->get(['id', 'announcement_type_id'])
            ->unique('announcement_type_id')
            ->pluck('id');

        DB::table('announcements')
            ->whereNull('deleted_at')
            ->whereNotIn('id', $keep)
            ->update(['deleted_at' => now()]);
    }

    public function down(): void
    {
        // Brought back one by one from the bin, as wished.
    }
};
