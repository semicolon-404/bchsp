<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What a patient or a relative comes to a hospital's site for:
 *
 * - faqs: questions people ask, each with its answer, at /faq.
 * - staff_links.audience: a link is for staff (as all were) or for the public; the public ones are the
 *   hospital's online services, at /services.
 * - announcements.event_on and event_until: the day (or days) of what an announcement is about, for the
 *   calendar at /calendar. Not when it is shown: when it happens.
 * - Two pages to be written by the hospital, started as outlines and not yet published: how to come for
 *   care, and how to donate. Nothing in them is made up; each heading says what to write under it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question', 300);
            $table->string('question_en', 300)->nullable();
            $table->text('answer');
            $table->text('answer_en')->nullable();
            $table->string('group', 100)->nullable(); // a heading to list it under
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('staff_links', function (Blueprint $table) {
            $table->string('audience', 20)->default('staff')->after('url'); // staff | public
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->date('event_on')->nullable()->after('ends_at');
            $table->date('event_until')->nullable()->after('event_on');

            $table->index('event_on');
        });

        $now = now();
        $outline = fn (array $headings) => implode('', array_map(fn (string $heading, string $write) => '<h2>'.e($heading).'</h2><p>('.e($write).')</p>', array_keys($headings), $headings));

        foreach ([
            'patient-guide' => ['ขั้นตอนการรับบริการ', 'Coming for care', [
                'ผู้ป่วยใหม่' => 'เขียนขั้นตอนตั้งแต่ยื่นบัตร ทำประวัติ คัดกรอง พบแพทย์ จนถึงรับยา',
                'ผู้ป่วยเก่า / มีนัด' => 'เขียนขั้นตอนสำหรับผู้ที่มีบัตรนัดหรือเคยมารับบริการแล้ว',
                'เอกสารที่ต้องนำมา' => 'เช่น บัตรประชาชน บัตรนัด ใบส่งตัว สมุดประจำตัวผู้ป่วย',
                'สิทธิการรักษา' => 'อธิบายสิทธิบัตรทอง ประกันสังคม ข้าราชการ และกรณีต้องชำระเงินเอง',
                'เวลาทำการ' => 'เวลาเปิดรับบัตร เวลาตรวจ และบริการนอกเวลาราชการ',
            ]],
            'donate' => ['ร่วมบริจาค', 'Donate', [
                'ช่องทางบริจาค' => 'ชื่อบัญชี ธนาคาร เลขที่บัญชี และ QR code ของโรงพยาบาล',
                'ใบเสร็จและการลดหย่อนภาษี' => 'วิธีขอใบเสร็จ และการบริจาคผ่านระบบ e-Donation',
                'สิ่งของที่โรงพยาบาลต้องการ' => 'รายการครุภัณฑ์หรือเวชภัณฑ์ที่ต้องการรับบริจาค',
                'ติดต่อ' => 'ชื่อผู้รับผิดชอบและเบอร์โทรสำหรับสอบถาม',
            ]],
        ] as $slug => [$title, $titleEn, $headings]) {
            if (! DB::table('site_pages')->where('slug', $slug)->exists()) {
                DB::table('site_pages')->insert([
                    'slug' => $slug, 'title' => $title, 'title_en' => $titleEn, 'body' => $outline($headings),
                    'section' => 'about', 'is_published' => false, 'show_in_footer' => false,
                    'position' => 90, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex(['event_on']);
            $table->dropColumn(['event_on', 'event_until']);
        });
        Schema::table('staff_links', fn (Blueprint $table) => $table->dropColumn('audience'));
        Schema::dropIfExists('faqs');
    }
};
