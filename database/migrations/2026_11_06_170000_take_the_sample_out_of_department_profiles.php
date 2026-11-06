<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * What the sample content lent to the departments' pages is taken back, on the live site too: a cover or round
 * picture whose file is a sample's ("demo-..."), and a headline, description or list of services, or a person, that
 * starts "[ตัวอย่าง]". What a department put there itself is never named so, and is left as it is.
 */
return new class extends Migration
{
    private const MARK = '[ตัวอย่าง]';

    public function up(): void
    {
        $sample = fn (?string $path) => $path !== null && str_starts_with(basename($path), 'demo-');
        $marked = fn (?string $text) => $text !== null && str_starts_with(ltrim($text), self::MARK);
        $files = [];

        foreach (DB::table('departments')->get() as $department) {
            $changes = [];

            foreach (['image_path', 'avatar_path'] as $field) {
                if ($sample($department->{$field} ?? null)) {
                    $files[] = $department->{$field};
                    $changes[$field] = null;
                }
            }

            foreach (['headline', 'description', 'services'] as $field) {
                if (property_exists($department, $field) && $marked($department->{$field})) {
                    $changes[$field] = null;
                }
            }

            if (property_exists($department, 'people') && $department->people) {
                $people = json_decode($department->people, true) ?: [];
                $kept = array_values(array_filter($people, function ($person) use ($marked, $sample, &$files) {
                    if (! $marked($person['name'] ?? null)) {
                        return true;
                    }
                    if ($sample($person['photo'] ?? null)) {
                        $files[] = $person['photo'];
                    }

                    return false;
                }));

                if (count($kept) !== count($people)) {
                    $changes['people'] = $kept ? json_encode($kept, JSON_UNESCAPED_UNICODE) : null;
                }
            }

            if ($changes) {
                DB::table('departments')->where('id', $department->id)->update($changes);
            }
        }

        if ($files) {
            Storage::disk('public')->delete($files);
        }

        // Clearing the samples later must not take away a picture a department has put there since.
        $manifest = storage_path('app/demo.json');
        if (is_file($manifest) && is_array($made = json_decode((string) file_get_contents($manifest), true))) {
            $made['profiles'] = [];
            file_put_contents($manifest, json_encode($made, JSON_UNESCAPED_UNICODE));
        }
    }

    public function down(): void
    {
        // The samples are not put back.
    }
};
