<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Festivals the home page dresses up for (App\Models\Festival), each between two days, and the krathongs the
 * departments float on the home page's river during Loy Krathong (App\Models\Krathong).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('festivals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('theme', 30);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('intensity', 10)->default('medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'starts_on', 'ends_on']);
        });

        Schema::create('krathongs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('festival_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('style', 20)->default('banana');
            $table->string('wish', 140);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('krathongs');
        Schema::dropIfExists('festivals');
    }
};
