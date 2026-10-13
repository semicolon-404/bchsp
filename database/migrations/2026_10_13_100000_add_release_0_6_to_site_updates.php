<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Release 0.6, written down for the public (/updates): every page of the site redesigned. */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('site_updates')->where('version', '0.6')->exists()) {
            return;
        }

        $items = [
            ['new', 'หน้าเกี่ยวกับเราเป็นหน้าโปรไฟล์ของโรงพยาบาล: วิสัยทัศน์ พันธกิจ ค่านิยม ประวัติ เวลาให้บริการ และหน่วยงาน ในหน้าเดียว'],
            ['new', 'หน้าข่าวประชาสัมพันธ์แบบใหม่: เรื่องล่าสุดจัดวางเด่น แยกตามประเภทพร้อมจำนวน และบอกกิจกรรมที่กำลังจะมาถึง'],
            ['new', 'หน้าแรกมีทางลัดไปสิ่งที่ผู้มารับบริการใช้บ่อย และเบอร์ติดต่อตัวใหญ่ท้ายหน้า'],
            ['improved', 'ทุกหน้าออกแบบใหม่ให้อ่านง่ายขึ้น มีหัวหน้าบอกชัดว่าอยู่หน้าไหน พร้อมช่องค้นหาและตัวเลขสรุปในหน้าที่ควรมี'],
            ['improved', 'หน้าหน่วยงานแต่ละการ์ดบอกที่ตั้ง เบอร์โทร และสถานะเปิดให้บริการในขณะนั้น'],
            ['improved', 'ตารางออกตรวจเน้นวันนี้ให้เห็นก่อน และเลือกดูวันอื่นได้ในปุ่มเดียว'],
            ['improved', 'เมื่อเปิดหน้าที่ไม่มีอยู่ จะมีทางไปหน้าหลัก ประกาศ และค้นหาให้ ไม่ทิ้งให้ค้างที่หน้าว่าง'],
        ];

        DB::table('site_updates')->insert([
            'version' => '0.6',
            'title' => 'ออกแบบใหม่ทุกหน้า ให้น่าอ่านและหาข้อมูลง่ายขึ้น',
            'title_en' => 'Every page redesigned, easier to read and to find things on',
            'status' => 'released',
            'released_on' => '2026-10-13',
            'items' => json_encode(array_map(fn (array $item) => ['kind' => $item[0], 'text' => $item[1], 'text_en' => null], $items), JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('site_updates')->where('version', '0.6')->delete();
    }
};
