<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * banners: pictures with an optional link that staff put on the home page for a
 * while: a wide banner across the top, or a poster in the grid of posters.
 * One of them can also open over the page when the site is first opened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);              // read aloud to people who cannot see the picture
            $table->string('image_path');
            $table->string('url', 500)->nullable();
            $table->string('placement', 20)->default('banner'); // banner | poster
            $table->boolean('is_popup')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
