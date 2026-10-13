<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Release 0.7 gains a line (/updates): readers can say an announcement was useful. */
return new class extends Migration
{
    private const TEXT = 'ใต้ประกาศแต่ละเรื่องมีปุ่ม "มีประโยชน์" ให้กดบอกโรงพยาบาลได้ในครั้งเดียว โดยไม่เก็บข้อมูลของผู้กด';

    public function up(): void
    {
        $release = DB::table('site_updates')->where('version', '0.7')->first();
        $items = $release ? (json_decode((string) $release->items, true) ?: []) : [];

        if (! $release || in_array(self::TEXT, array_column($items, 'text'), true)) {
            return;
        }

        $items[] = ['kind' => 'new', 'text' => self::TEXT, 'text_en' => null];

        DB::table('site_updates')->where('id', $release->id)->update(['items' => json_encode($items, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
    }

    public function down(): void
    {
        $release = DB::table('site_updates')->where('version', '0.7')->first();

        if ($release) {
            $items = array_values(array_filter(json_decode((string) $release->items, true) ?: [], fn (array $item) => ($item['text'] ?? null) !== self::TEXT));
            DB::table('site_updates')->where('id', $release->id)->update(['items' => json_encode($items, JSON_UNESCAPED_UNICODE)]);
        }
    }
};
