<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * More of a department's profile: the services it gives (one to a line), an email address,
 * and the people who work there (a name, a position and a photograph each).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->text('services')->nullable()->after('description_en');
            $table->string('email', 255)->nullable()->after('phone');
            $table->json('people')->nullable()->after('avatar_path');
        });
    }

    public function down(): void
    {
        Schema::table('departments', fn (Blueprint $table) => $table->dropColumn(['services', 'email', 'people']));
    }
};
