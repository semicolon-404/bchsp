<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Once, at the hospital's asking: its core values (TAAMDEE) with what each means, where they were still the bare
 * words. Written differently by hand in Site settings since, they are left as they are.
 */
return new class extends Migration
{
    private const BARE = "T — Teamwork\nA — Achievement\nA — Agility\nM — Management\nD — Development\nE — Ethic\nE — Empowerment";

    private const SPELT = "T (Teamwork): ทำงานเป็นทีมร่วมกับเครือข่าย\n"
        ."A (Achievement): มุ่งผลสัมฤทธิ์ ยึดผู้ป่วยเป็นศูนย์กลาง\n"
        ."A (Agility): คล่องตัว ปรับตัวไว ยืดหยุ่น\n"
        ."M (Management): บริหารและตัดสินใจด้วยข้อมูลจริง\n"
        ."D (Development): พัฒนาและปรับปรุงงานอย่างต่อเนื่อง\n"
        ."E (Ethic): ยึดมั่นในจริยธรรมและมาตรฐานวิชาชีพ\n"
        ."E (Empowerment): เสริมพลังเจ้าหน้าที่ในการพัฒนางาน";

    public function up(): void
    {
        $now = json_decode((string) DB::table('settings')->where('key', 'identity.values')->value('value'), true);

        if (is_string($now) && trim(str_replace("\r", '', $now)) === self::BARE) {
            DB::table('settings')->where('key', 'identity.values')->update(['value' => json_encode(self::SPELT, JSON_UNESCAPED_UNICODE)]);
            Cache::forget('site-settings');
        }
    }

    public function down(): void
    {
        // The values are the hospital's once written: left as they are.
    }
};
