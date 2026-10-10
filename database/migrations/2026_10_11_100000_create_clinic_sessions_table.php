<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * clinic_sessions: when each clinic and service is open during the week
 * ("Diabetes clinic, Tuesdays 08.00–12.00"). What visitors most often phone to ask.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('name_en', 150)->nullable();
            // The unit that runs it, when it is one of the departments on the site.
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->json('days');                    // the days of the week it is held: 0 (Sunday) to 6 (Saturday)
            $table->string('starts_at', 5);          // "08:00"
            $table->string('ends_at', 5);            // "16:00"
            $table->string('note', 200)->nullable(); // "By appointment", "Except public holidays"
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_sessions');
    }
};
