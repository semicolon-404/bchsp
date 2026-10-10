<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Statistics an executive can be shown, still with nothing kept about any visitor:
 *
 * - site_visits counts pages opened per day for each part of the site (news, procurement, ITA...),
 *   not only in all. What was counted before this is kept, under "other".
 * - announcements.views and blog_posts.views count how often each one was opened.
 */
return new class extends Migration
{
    public function up(): void
    {
        $before = DB::table('site_visits')->get();

        Schema::drop('site_visits');

        Schema::create('site_visits', function (Blueprint $table) {
            $table->date('day');
            $table->string('section', 20);
            $table->unsignedBigInteger('views')->default(0);

            $table->primary(['day', 'section']);
        });

        foreach ($before as $row) {
            DB::table('site_visits')->insert(['day' => $row->day, 'section' => 'other', 'views' => $row->views]);
        }

        Schema::table('announcements', function (Blueprint $table) {
            $table->unsignedBigInteger('views')->default(0);
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('views')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', fn (Blueprint $table) => $table->dropColumn('views'));
        Schema::table('announcements', fn (Blueprint $table) => $table->dropColumn('views'));

        $before = DB::table('site_visits')->selectRaw('day, sum(views) as views')->groupBy('day')->get();

        Schema::drop('site_visits');

        Schema::create('site_visits', function (Blueprint $table) {
            $table->date('day')->primary();
            $table->unsignedBigInteger('views')->default(0);
        });

        foreach ($before as $row) {
            DB::table('site_visits')->insert(['day' => $row->day, 'views' => $row->views]);
        }
    }
};
