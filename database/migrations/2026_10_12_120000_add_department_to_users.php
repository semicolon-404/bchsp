<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users.department_id: the unit of the hospital an account posts for. An author with one is that unit's
 * own account: it posts on the unit's board and keeps the unit's page, and sees nothing of the others.
 * An author without one posts for the whole hospital, as before. Removing a unit leaves its accounts,
 * with no unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('department_id'));
    }
};
