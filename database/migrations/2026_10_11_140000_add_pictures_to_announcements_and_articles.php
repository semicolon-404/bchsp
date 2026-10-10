<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - announcements.image_path: the "one page" picture many announcements are made as (an infographic,
 *   upright or wide), shown whole on the announcement's page. announcements.in_slider puts that same
 *   picture among the slides of the home page, so it is uploaded once.
 * - blog_posts.gallery: the photographs of an activity, shown as an album under the article.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('body_en');
            $table->boolean('in_slider')->default(false)->after('image_path');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->json('gallery')->nullable()->after('cover_path');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', fn (Blueprint $table) => $table->dropColumn('gallery'));
        Schema::table('announcements', fn (Blueprint $table) => $table->dropColumn(['image_path', 'in_slider']));
    }
};
