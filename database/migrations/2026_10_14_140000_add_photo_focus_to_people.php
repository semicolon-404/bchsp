<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which part of a person's photograph shows in the round picture: where the middle of the circle falls on
 * the photograph, as two figures from 0 to 100 ("50 18"). Nothing kept means near the top, where a face is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('photo_focus', 12)->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('photo_focus');
        });
    }
};
