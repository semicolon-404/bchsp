<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * departments: the units of the hospital a visitor may need to find or phone:
 * what each does, where it is, its number and its hours.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('name_en', 150)->nullable();
            $table->string('slug', 200)->unique(); // the public URL: /departments/{slug}
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->string('location', 200)->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('hours', 500)->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
