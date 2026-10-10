<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the grouped menus need (App\Support\Menus):
 *
 * - site_pages.section: a page can be listed under "About us" in the menu.
 * - documents.section and documents.year: a document belongs to the Downloads page or to the
 *   ITA page, where documents are filed by fiscal year.
 * - staff_links: the services staff reach from the "For staff" page. Kept apart from the
 *   public menu, which holds only what the public comes for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_pages', function (Blueprint $table) {
            $table->string('section', 20)->nullable()->after('slug')->index();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('section', 20)->default('downloads')->after('title_en')->index();
            $table->unsignedSmallInteger('year')->nullable()->after('section'); // the fiscal year as written in Thailand: 2569
        });

        Schema::create('staff_links', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('url', 500);
            $table->string('group', 100)->nullable();
            $table->string('note', 200)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_links');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['section']);
            $table->dropColumn(['section', 'year']);
        });

        Schema::table('site_pages', function (Blueprint $table) {
            $table->dropIndex(['section']);
            $table->dropColumn('section');
        });
    }
};
