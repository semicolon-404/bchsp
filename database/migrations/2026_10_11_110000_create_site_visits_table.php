<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * site_visits: how many pages of the site were opened on each day, and nothing
 * else (App\Support\Visits). One row a day: a number. No address, no browser,
 * no page: nothing here can be traced to a person.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->date('day')->primary();
            $table->unsignedBigInteger('views')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
