<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * site_pages: pages of text an administrator writes in the panel (System > Pages):
 * the hospital's history, its vision and mission, how to get there.
 * Written in Thai; the "_en" columns hold an optional English version.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique(); // the public URL: /p/{slug}
            $table->string('title', 120);
            $table->string('title_en', 120)->nullable();
            $table->longText('body');
            $table->longText('body_en')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_pages');
    }
};
