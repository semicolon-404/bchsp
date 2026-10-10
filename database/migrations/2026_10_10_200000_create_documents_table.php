<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * documents: files the public can download from /downloads: forms, price
 * lists, regulations. Unlike an announcement's files, these stay for as long
 * as they are useful and are found by what they are, not by when they were posted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 250);
            $table->string('title_en', 250)->nullable();
            // The heading it is listed under ("Forms", "Regulations"); blank = under "Other documents".
            $table->string('group', 100)->nullable();
            $table->string('file_path');
            $table->string('file_name')->nullable(); // the name it was uploaded under
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
