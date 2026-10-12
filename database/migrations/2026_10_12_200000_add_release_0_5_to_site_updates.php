<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Release 0.5, written down for the public (/updates): what patients and relatives come for. */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('site_updates')->where('version', '0.5')->exists()) {
            return;
        }

        $items = [
            ['new', 'ปฏิทินกิจกรรม: ดูได้ว่าแต่ละวันมีออกหน่วย รับบริจาคโลหิต หรืองดบริการคลินิกใด'],
            ['new', 'หน้าคำถามที่พบบ่อย และหน้ารวมบริการออนไลน์ของโรงพยาบาล'],
            ['new', 'เบอร์โทรศัพท์ของทุกหน่วยงานในหน้าเดียว พิมพ์ค้นหาได้'],
            ['new', 'เพิ่มเว็บไซต์ไว้บนหน้าจอโทรศัพท์ได้เหมือนแอป และยังเปิดดูเบอร์ฉุกเฉินได้แม้ไม่มีอินเทอร์เน็ต'],
            ['new', 'ประกาศที่ลงใหม่ภายใน 7 วันมีป้าย "ใหม่" และมีรายการเรื่องที่มีผู้อ่านมากที่สุด'],
            ['new', 'ปุ่มพิมพ์ประกาศ จัดหน้าให้เหลือเฉพาะเนื้อหา และติดตามประกาศใหม่ผ่าน RSS ได้'],
            ['improved', 'หน้าเกี่ยวกับเราและหน้าของหน่วยงานบอกวันที่แก้ไขล่าสุด'],
        ];

        DB::table('site_updates')->insert([
            'version' => '0.5',
            'title' => 'สิ่งที่ผู้มารับบริการมองหา อยู่ใกล้มือขึ้น',
            'title_en' => 'What patients look for, closer to hand',
            'status' => 'released',
            'released_on' => '2026-10-12',
            'items' => json_encode(array_map(fn (array $item) => ['kind' => $item[0], 'text' => $item[1], 'text_en' => null], $items), JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('site_updates')->where('version', '0.5')->delete();
    }
};
