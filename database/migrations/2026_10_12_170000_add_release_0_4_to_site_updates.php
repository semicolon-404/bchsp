<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Release 0.4, written down for the public (/updates): what came after 0.3 on the same day. */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('site_updates')->where('version', '0.4')->exists()) {
            return;
        }

        $items = [
            ['new', 'หน้า "ความคืบหน้าของเว็บไซต์" บอกว่าเว็บมีอะไรใหม่ในแต่ละรุ่น และเลขรุ่นล่าสุดอยู่ที่ส่วนท้ายของทุกหน้า'],
            ['new', 'หน้าแรกมีแถว "ข่าวจากหน่วยงาน" พาไปหน้าของหน่วยงานที่เพิ่งลงข่าว'],
            ['new', 'หน่วยงานปักหมุดเรื่องสำคัญไว้บนสุดของหน้าตัวเองได้'],
            ['improved', 'หน้าแรกมีภาพของโรงพยาบาลเป็นพื้นหลัง และแต่ละรายการมีสัญลักษณ์กำกับ อ่านได้เร็วขึ้น'],
            ['improved', 'การเปลี่ยนหน้าและการเลื่อนอ่านนุ่มนวลขึ้น (ปิดเองสำหรับผู้ที่ตั้งเครื่องให้ลดการเคลื่อนไหว)'],
            ['improved', 'หน้าที่ยังไม่มีข้อมูลบอกชัดเจนขึ้นว่ายังไม่มีอะไร'],
        ];

        DB::table('site_updates')->insert([
            'version' => '0.4',
            'title' => 'เว็บไซต์ดูมีชีวิตขึ้น และบอกความคืบหน้าของตัวเอง',
            'title_en' => 'A livelier website that tells what is new in it',
            'status' => 'released',
            'released_on' => '2026-10-12',
            'items' => json_encode(array_map(fn (array $item) => ['kind' => $item[0], 'text' => $item[1], 'text_en' => null], $items), JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('site_updates')->where('version', '0.4')->delete();
    }
};
