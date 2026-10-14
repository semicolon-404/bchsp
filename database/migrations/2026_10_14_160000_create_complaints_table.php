<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complaints, suggestions and praise sent in through the site (/complaint), and what the hospital did about
 * each. The one place the site keeps anything a visitor wrote about themselves: so the columns that say who
 * they are hold it encrypted (App\Models\Complaint), the files they attach are kept off the public disk, and
 * the code they follow the matter with is kept only as a hash, like a password.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            // What the sender is given to follow it by: "CMP-691014-AB7K", and six digits kept only as a hash.
            $table->string('code', 20)->unique();
            $table->string('pin_hash');
            $table->string('kind', 20);
            $table->string('title', 255);
            $table->text('detail');
            $table->date('happened_on')->nullable();
            $table->string('place', 255)->nullable();
            $table->foreignId('work_group_id')->nullable()->constrained()->nullOnDelete();
            // Who sent it, when they chose to say: each kept encrypted, so they are text and not searched.
            $table->boolean('is_anonymous')->default(false);
            $table->text('name')->nullable();
            $table->text('phone')->nullable();
            $table->text('email')->nullable();
            $table->text('line_id')->nullable();
            $table->text('address')->nullable();
            // Paths on the private disk, with the names the files came under.
            $table->json('attachments')->nullable();
            $table->string('status', 20)->default('received');
            // What the sender reads on the tracking page; and what only staff read.
            $table->text('reply')->nullable();
            $table->text('internal_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
