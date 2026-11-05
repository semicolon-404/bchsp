<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visitors float krathongs too, at /loykrathong, under a name of their choosing (no account); an administrator
 * can take one off the river without deleting it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('krathongs', function (Blueprint $table) {
            $table->string('name', 40)->nullable()->after('user_id');
            $table->boolean('is_hidden')->default(false)->after('wish');
            $table->index(['festival_id', 'is_hidden', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('krathongs', function (Blueprint $table) {
            $table->dropIndex(['festival_id', 'is_hidden', 'created_at']);
            $table->dropColumn(['name', 'is_hidden']);
        });
    }
};
