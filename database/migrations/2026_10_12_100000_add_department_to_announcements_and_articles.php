<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * announcements.department_id and blog_posts.department_id: the unit of the hospital that put it out,
 * when one did. Each unit's page (/departments/{slug}) is its board: its own posters, announcements
 * and articles. Deleting a unit leaves what it put out, with no unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['announcements', 'blog_posts'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['announcements', 'blog_posts'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('department_id'));
        }
    }
};
