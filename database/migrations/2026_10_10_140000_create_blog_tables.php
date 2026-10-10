<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The blog: health knowledge and news of what the hospital has been doing.
 * Everything is written in Thai; the "_en" columns hold an optional English version.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('name_en', 80)->nullable();
            $table->string('slug', 160)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            // Removing a category leaves its articles uncategorised rather than deleting them.
            $table->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->string('title_en', 180)->nullable();
            $table->string('slug', 200)->unique(); // the public URL: /blog/{slug}
            $table->string('excerpt', 300)->nullable();
            $table->string('excerpt_en', 300)->nullable();
            $table->longText('body');
            $table->longText('body_en')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('status', 20)->default('draft'); // draft | published
            $table->timestamp('published_at')->nullable();  // a future date schedules the article
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['blog_category_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_categories');
    }
};
