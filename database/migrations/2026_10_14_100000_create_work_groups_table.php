<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How the hospital is organised, for the pages "Organisation structure" and "Executives" under About us.
 *
 * work_groups: a group of work (กลุ่มงาน) as the organisation chart has it: who heads it, how many work in
 * it, and the units of work (งาน) inside it. Not the same as a department of the site, which is a place a
 * patient looks for and has a profile and a board; a group can point at one, when one stands for it.
 *
 * The two pages are pages of the "About us" group like the others, so they sit in its menu and are turned
 * through with the rest; what they show is drawn from here and not written as text (SitePageController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('name_en', 150)->nullable();
            $table->string('head_name', 150)->nullable();
            // What the head is by profession ("Pharmacist"), as the register of staff has it.
            $table->string('head_position', 150)->nullable();
            $table->string('head_photo_path')->nullable();
            $table->unsignedSmallInteger('staff_count')->nullable();
            // The units of work inside it, a name to a line.
            $table->json('units')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $structure = DB::table('site_pages')->where('slug', 'structure')->first();

        if (! $structure) {
            $position = (int) DB::table('site_pages')->where('section', 'about')->max('position') + 1;

            DB::table('site_pages')->insert([
                'slug' => 'structure', 'section' => 'about', 'title' => 'โครงสร้างองค์กร', 'title_en' => 'Organisation structure',
                'body' => '', 'is_published' => true, 'show_in_footer' => false, 'position' => $position, 'created_at' => $now, 'updated_at' => $now,
            ]);
        } else {
            $position = (int) $structure->position;
        }

        if (! DB::table('site_pages')->where('slug', 'executives')->exists()) {
            // Straight after the structure: the pages after it move down one.
            DB::table('site_pages')->where('section', 'about')->where('position', '>', $position)->increment('position');

            DB::table('site_pages')->insert([
                'slug' => 'executives', 'section' => 'about', 'title' => 'ทำเนียบผู้บริหาร', 'title_en' => 'Executives',
                'body' => '', 'is_published' => true, 'show_in_footer' => false, 'position' => $position + 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('site_pages')->where('slug', 'executives')->delete();
        Schema::dropIfExists('work_groups');
    }
};
