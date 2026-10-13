<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What readers typed into the site's search, a day at a time: the words, how often, and how much was found.
 * Nothing about who searched. It tells the hospital what people look for and do not find (App\Support\Searches).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_terms', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('term', 80);
            $table->unsignedInteger('searches')->default(0);
            // What the last search of the day found: 0 is the thing worth knowing.
            $table->unsignedInteger('found')->default(0);
            $table->unique(['day', 'term']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_terms');
    }
};
