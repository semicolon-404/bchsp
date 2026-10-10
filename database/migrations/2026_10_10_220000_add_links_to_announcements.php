<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An announcement's documents can be kept elsewhere (Google Drive) and linked,
 * instead of being uploaded: the server's disk is small, and procurement
 * notices arrive every month with several files each.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            // A list of rows: [['label' => 'Winning bid, March', 'url' => 'https://drive.google.com/...'], ...]
            $table->json('links')->nullable()->after('attachment_names');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('links');
        });
    }
};
