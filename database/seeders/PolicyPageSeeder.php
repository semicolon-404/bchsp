<?php

namespace Database\Seeders;

use App\Models\SitePage;
use Illuminate\Database\Seeder;

/**
 * The privacy notice a government website is expected to carry, written to say
 * what this site really does: it keeps nothing about its visitors. Run once on
 * a new installation (php artisan db:seed); a page that already has this
 * address is left alone, so what the hospital has since rewritten is kept.
 *
 * If the site ever starts to collect something (a form, an analytics service),
 * this page has to change with it.
 */
class PolicyPageSeeder extends Seeder
{
    public function run(): void
    {
        if (SitePage::where('slug', 'privacy')->exists()) {
            return;
        }

        SitePage::create([
            'slug' => 'privacy',
            'title' => 'นโยบายความเป็นส่วนตัวและนโยบายเว็บไซต์',
            'title_en' => 'Privacy and website policy',
            'show_in_footer' => true,
            'position' => 90,
            'body' => <<<'HTML'
                <h2>เว็บไซต์นี้ไม่เก็บข้อมูลส่วนบุคคลของผู้เข้าชม</h2>
                <p>ท่านเข้าชมเว็บไซต์นี้ได้โดยไม่ต้องสมัครสมาชิก ไม่ต้องกรอกชื่อ เบอร์โทรศัพท์ หรือข้อมูลสุขภาพใดๆ เว็บไซต์ไม่มีแบบฟอร์มรับข้อมูล และไม่ใช้บริการติดตามพฤติกรรมหรือโฆษณาของบุคคลที่สาม</p>
                <h2>สิ่งที่เว็บไซต์จดจำไว้ในอุปกรณ์ของท่าน</h2>
                <ul>
                <li><strong>ภาษาที่เลือก</strong> เก็บเป็นคุกกี้ในเบราว์เซอร์ของท่าน เพื่อแสดงภาษาเดิมในครั้งถัดไป</li>
                <li><strong>โหมดสว่างหรือมืด และโปสเตอร์ที่ท่านกดปิดแล้ว</strong> เก็บไว้ในเบราว์เซอร์ของท่านเท่านั้น ไม่ได้ส่งมาที่เว็บไซต์</li>
                </ul>
                <p>ข้อมูลเหล่านี้ไม่ระบุตัวบุคคล และท่านลบได้เองจากการตั้งค่าเบราว์เซอร์</p>
                <h2>สถิติการเข้าชม</h2>
                <p>เว็บไซต์นับจำนวนครั้งที่มีการเปิดหน้าในแต่ละวันเป็นตัวเลขรวม โดยไม่บันทึกหมายเลข IP ชนิดอุปกรณ์ หรือหน้าที่แต่ละท่านเปิด</p>
                <h2>บริการภายนอกที่เชื่อมโยงจากเว็บไซต์</h2>
                <ul>
                <li><strong>แผนที่</strong> ในหน้าติดต่อเรา โหลดจาก Google Maps เมื่อท่านกดปุ่ม "แสดงแผนที่" เท่านั้น</li>
                <li><strong>แบบร้องเรียน แบบเสนอแนะ และแบบประเมินความพึงพอใจ</strong> อยู่บน Google Forms ข้อมูลที่ท่านกรอกในแบบฟอร์มเหล่านั้นอยู่ภายใต้การดูแลของโรงพยาบาลและนโยบายของ Google</li>
                <li><strong>เอกสารบางรายการ</strong> เก็บไว้ใน Google Drive และเปิดในหน้าต่างใหม่</li>
                </ul>
                <h2>ข้อมูลของเจ้าหน้าที่</h2>
                <p>ระบบจัดการเว็บไซต์บันทึกการเข้าสู่ระบบและการแก้ไขเนื้อหาของเจ้าหน้าที่ (ชื่อบัญชี เวลา หมายเลข IP) เพื่อความปลอดภัยและการตรวจสอบย้อนหลัง ตามพระราชบัญญัติว่าด้วยการกระทำความผิดเกี่ยวกับคอมพิวเตอร์</p>
                <h2>ติดต่อ</h2>
                <p>หากมีข้อสงสัยเกี่ยวกับนโยบายนี้ ติดต่อโรงพยาบาลได้ตามช่องทางในหน้าติดต่อเรา</p>
                HTML,
        ]);
    }
}
