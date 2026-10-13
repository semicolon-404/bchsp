<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Release 0.7, written down for the public (/updates): the feed of the departments, and the staff panel's new look. */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('site_updates')->where('version', '0.7')->exists()) {
            return;
        }

        $items = [
            ['new', 'หน้าฟีดข่าวจากหน่วยงาน: อ่านสิ่งที่แต่ละหน่วยงานโพสต์เรียงจากใหม่ไปเก่าได้ในหน้าเดียว หรือเลือกดูเฉพาะหน่วยงานที่สนใจ'],
            ['new', 'ติดตามโพสต์ใหม่ของหน่วยงานที่สนใจผ่าน RSS ได้'],
            ['new', 'ดูภาพในอัลบั้มแบบขยายเต็มจอ เลื่อนดูภาพก่อนหน้าและถัดไปได้โดยไม่ต้องออกจากหน้า'],
            ['improved', 'ระบบหลังบ้านสำหรับเจ้าหน้าที่ออกแบบใหม่ มีทางลัดเขียนโพสต์และบอกงานที่ค้างตั้งแต่หน้าแรก ทำให้ลงข่าวได้เร็วขึ้น'],
            ['improved', 'ทุกหน้าปรับให้ใช้บนมือถือได้สบายขึ้น และมีปุ่มกลับขึ้นด้านบนเมื่อเลื่อนลงมาไกล'],
        ];

        DB::table('site_updates')->insert([
            'version' => '0.7',
            'title' => 'ฟีดข่าวจากหน่วยงาน และระบบหลังบ้านโฉมใหม่',
            'title_en' => 'A feed of what the departments post, and a new look for the staff panel',
            'status' => 'released',
            'released_on' => '2026-10-13',
            'items' => json_encode(array_map(fn (array $item) => ['kind' => $item[0], 'text' => $item[1], 'text_en' => null], $items), JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('site_updates')->where('version', '0.7')->delete();
    }
};
