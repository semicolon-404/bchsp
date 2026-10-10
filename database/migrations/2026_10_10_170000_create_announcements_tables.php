<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Announcements: what the hospital has to tell the public, sorted by type
 * (public relations news, procurement, job openings), often with PDF files
 * attached, shown between two dates.
 *
 * The three types every hospital site starts with are put in here, so a new
 * installation has them; an administrator can rename, add or remove types.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('name_en', 80)->nullable();
            $table->string('slug', 160)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            // An announcement keeps its place in the list when its type or its author is removed.
            $table->foreignId('announcement_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 250);
            $table->string('title_en', 250)->nullable();
            $table->string('slug', 200)->unique(); // the public URL: /announcements/{slug}
            $table->longText('body')->nullable();
            $table->longText('body_en')->nullable();
            // The attached files: their paths on the public disk, and the name each was uploaded under.
            $table->json('attachments')->nullable();
            $table->json('attachment_names')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('starts_at')->nullable(); // shown from; a future date schedules it
            $table->timestamp('ends_at')->nullable();   // shown until; blank = no end
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
            $table->index(['announcement_type_id', 'starts_at']);
        });

        $now = now();

        DB::table('announcement_types')->insert([
            ['name' => 'ข่าวประชาสัมพันธ์', 'name_en' => 'News', 'slug' => 'news', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'จัดซื้อจัดจ้าง/ประกวดราคา', 'name_en' => 'Procurement', 'slug' => 'procurement', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'รับสมัครงาน', 'name_en' => 'Job openings', 'slug' => 'jobs', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('announcement_types');
    }
};
