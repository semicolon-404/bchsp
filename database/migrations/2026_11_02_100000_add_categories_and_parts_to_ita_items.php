<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An ITA item has a heading of the assessment it sits under ("1. การเปิดเผยข้อมูล"), and parts of its own: the
 * numbered documents the assessment asks for under it, each in the round it is due (the whole year, the second
 * quarter's six-month round, the fourth quarter's twelve-month one) and with its own evidence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ita_items', function (Blueprint $table) {
            $table->string('category', 200)->nullable()->after('code');
            // [{round: year|q2|q4, title, attachments: [path], attachment_names: {path: name}, links: [{label, url}]}]
            $table->json('parts')->nullable()->after('links');
        });
    }

    public function down(): void
    {
        Schema::table('ita_items', function (Blueprint $table) {
            $table->dropColumn(['category', 'parts']);
        });
    }
};
