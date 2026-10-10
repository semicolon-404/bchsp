<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ita_items: the Integrity and Transparency Assessment, as a part of the site of its own.
 *
 * Each fiscal year has a list of numbered items ("MOIT 1", "MOIT 2"...); under each the hospital
 * publishes its evidence, as PDF files kept here or as links to documents kept elsewhere.
 *
 * This replaces filing ITA documents among the downloads (documents.section, documents.year),
 * which was too small a place for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ita_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');        // the fiscal year as written in Thailand: 2569
            $table->unsignedSmallInteger('number');      // its place in the year's list: 1, 2, 3...
            $table->string('code', 30);                  // what it is called: "MOIT 1"
            $table->string('title', 500);
            $table->text('description')->nullable();
            // The evidence: files on the public disk with the name each was uploaded under, and links elsewhere.
            $table->json('attachments')->nullable();
            $table->json('attachment_names')->nullable();
            $table->json('links')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['year', 'number']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['section']);
            $table->dropColumn(['section', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('section', 20)->default('downloads')->after('title_en')->index();
            $table->unsignedSmallInteger('year')->nullable()->after('section');
        });

        Schema::dropIfExists('ita_items');
    }
};
