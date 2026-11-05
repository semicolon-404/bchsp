<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Popups set up on their own (App\Models\SitePopup), apart from the announcements. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_popups', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('button_label', 60)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->string('second_label', 60)->nullable();
            $table->string('second_url', 500)->nullable();
            $table->string('size', 10)->default('medium');
            $table->string('placement', 10)->default('center');
            $table->string('accent', 7)->nullable();
            $table->string('pages', 10)->default('home');
            $table->string('frequency', 10)->default('once');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_popups');
    }
};
