<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A department's page becomes its profile: beside the picture it had (now the cover across the top),
 * a small round picture of its own, a line under its name, and where else it is found (Facebook, LINE).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('image_path');
            $table->string('headline', 200)->nullable()->after('name_en');
            $table->string('headline_en', 200)->nullable()->after('headline');
            $table->string('facebook_url', 500)->nullable()->after('hours');
            $table->string('line_url', 500)->nullable()->after('facebook_url');
        });
    }

    public function down(): void
    {
        Schema::table('departments', fn (Blueprint $table) => $table->dropColumn(['avatar_path', 'headline', 'headline_en', 'facebook_url', 'line_url']));
    }
};
