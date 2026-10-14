<?php

use App\Support\Organisation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One register of the people the site names: each kept once, with a position and a photograph, and named
 * from wherever they appear (the head of a group of work, the director). Until now a head's name and
 * photograph were written on the group itself, so someone heading two groups was written twice.
 *
 * What was written on the groups and in the settings is moved into the register here, each person once;
 * the old columns stay as they were and are read only where a group has no one from the register.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            // What they are by profession or position ("Pharmacist").
            $table->string('position', 150)->nullable();
            $table->string('photo_path')->nullable();
            // The group of work they belong to, when it is said.
            $table->foreignId('work_group_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('work_groups', function (Blueprint $table) {
            $table->foreignId('head_id')->nullable()->after('name_en')->constrained('people')->nullOnDelete();
        });

        $now = now();
        $known = [];
        $order = 0;
        $person = function (string $name, ?string $position, ?string $photo, ?int $group) use (&$known, &$order, $now): int {
            $key = Organisation::bareName($name);

            return $known[$key] ??= DB::table('people')->insertGetId([
                'name' => trim($name), 'position' => $position ?: null, 'photo_path' => $photo ?: null, 'work_group_id' => $group,
                'is_active' => true, 'sort_order' => ++$order, 'created_at' => $now, 'updated_at' => $now,
            ]);
        };

        // The director first, so that the register begins with whoever runs the hospital.
        $setting = fn (string $key) => json_decode((string) DB::table('settings')->where('key', $key)->value('value'), true);

        if (Schema::hasTable('settings') && filled($director = $setting('org.director_name'))) {
            $id = $person((string) $director, 'ผู้อำนวยการโรงพยาบาล', $setting('org.director_photo'), null);

            DB::table('settings')->updateOrInsert(['key' => 'org.director_person'], ['value' => json_encode($id), 'created_at' => $now, 'updated_at' => $now]);
        }

        foreach (DB::table('work_groups')->orderBy('sort_order')->orderBy('id')->get() as $group) {
            if (filled($group->head_name)) {
                DB::table('work_groups')->where('id', $group->id)->update([
                    'head_id' => $person($group->head_name, $group->head_position, $group->head_photo_path, $group->id),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('work_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_id');
        });

        Schema::dropIfExists('people');
        DB::table('settings')->where('key', 'org.director_person')->delete();
    }
};
