<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * site_updates: what has changed on the website, release by release, for the public to read at /updates
 * (and what is being worked on next). The footer of every page names the newest one.
 *
 * The releases up to the day this was written are put in here, so the page does not start empty;
 * an administrator writes the ones after, and can reword these.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_updates', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20);
            $table->string('title', 200);
            $table->string('title_en', 200)->nullable();
            // What changed: a list of [kind: new | improved | fixed, text, text_en].
            $table->json('items')->nullable();
            // released: out, on that day. planned: being worked on, shown apart with no day promised.
            $table->string('status', 20)->default('released');
            $table->date('released_on')->nullable();
            $table->timestamps();

            $table->index(['status', 'released_on']);
        });

        $now = now();
        $release = fn (string $version, string $day, string $title, string $titleEn, array $items) => [
            'version' => $version, 'title' => $title, 'title_en' => $titleEn, 'status' => 'released', 'released_on' => $day,
            'items' => json_encode(array_map(fn (array $item) => ['kind' => $item[0], 'text' => $item[1], 'text_en' => null], $items), JSON_UNESCAPED_UNICODE),
            'created_at' => $now, 'updated_at' => $now,
        ];

        DB::table('site_updates')->insert([
            $release('0.1', '2026-10-10', 'วางโครงเว็บไซต์ใหม่ของโรงพยาบาล', 'The groundwork of the hospital\'s new website', [
                ['new', 'ประกาศแยกตามประเภท: ข่าวประชาสัมพันธ์ จัดซื้อจัดจ้าง รับสมัครงาน แนบไฟล์ PDF หรือลิงก์ Google Drive ได้'],
                ['new', 'หน้า ITA แยกตามปีงบประมาณ พร้อมเอกสารหลักฐานของแต่ละข้อ'],
                ['new', 'ตารางคลินิกรายสัปดาห์ และหน้าของแต่ละหน่วยงาน'],
                ['new', 'ค้นหาทั้งเว็บไซต์ ดาวน์โหลดแบบฟอร์ม และหน้าติดต่อพร้อมแผนที่'],
                ['new', 'เลือกภาษาไทยหรืออังกฤษ และโหมดสว่างหรือมืดได้'],
                ['improved', 'ใช้งานได้ทั้งบนโทรศัพท์ แท็บเล็ต และคอมพิวเตอร์'],
            ]),
            $release('0.2', '2026-10-11', 'หน้าแรกใหม่ และประกาศที่อ่านง่ายขึ้น', 'A new home page, and announcements that read more easily', [
                ['new', 'หน้าแรกบอกทันทีว่าวันนี้คลินิกไหนเปิดอยู่'],
                ['new', 'ประกาศแบบภาพแผ่นเดียว (one page) แสดงเต็มภาพ และนำขึ้นสไลด์หน้าแรกได้'],
                ['new', 'อัลบั้มภาพกิจกรรมใต้ข่าว'],
                ['improved', 'รายการประกาศเลือกดูแบบแถวหรือแบบการ์ดพร้อมภาพหน้าปกได้'],
                ['improved', 'ประเภทประกาศมีสัญลักษณ์และสีของตัวเอง แยกได้ในพริบตา'],
            ]),
            $release('0.3', '2026-10-12', 'หน่วยงานมีหน้าโปรไฟล์และโพสต์ข่าวของตัวเองได้', 'Departments have a profile and post their own news', [
                ['new', 'หน้าโปรไฟล์ของแต่ละหน่วยงาน: ภาพปก บริการ บุคลากร เวลาให้บริการ และข่าวของหน่วยงาน'],
                ['new', 'หน้าโปรไฟล์บอกว่าหน่วยงานเปิดให้บริการอยู่หรือไม่ในขณะนั้น'],
                ['new', 'ค้นประกาศตามช่วงเวลา: วันนี้ สัปดาห์นี้ เดือนนี้ หรือกำหนดวันเอง และตามหน่วยงาน'],
                ['new', 'แชร์ประกาศไป LINE และ Facebook หรือคัดลอกลิงก์ได้ในปุ่มเดียว'],
                ['new', 'อ่านเอกสาร PDF ได้ในหน้าประกาศ โดยไม่ต้องเปิดหน้าใหม่'],
                ['improved', 'ข่าว โปสเตอร์ ความรู้สุขภาพ และภาพกิจกรรม รวมอยู่ในที่เดียว ค้นหาง่ายขึ้น'],
                ['improved', 'หน้าในหมวดเกี่ยวกับเรา เลื่อนไปหน้าถัดไปได้โดยไม่ต้องย้อนกลับ'],
                ['fixed', 'ช่องเลือกวันที่ล้นขอบจอบนโทรศัพท์'],
            ]),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_updates');
    }
};
