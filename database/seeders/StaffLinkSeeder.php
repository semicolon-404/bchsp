<?php

namespace Database\Seeders;

use App\Models\StaffLink;
use Illuminate\Database\Seeder;

/**
 * The staff services and related sites linked from the hospital's previous
 * website, as a starting point for the "For staff" page. Run once on a new
 * installation (php artisan db:seed); it adds nothing when links already exist.
 */
class StaffLinkSeeder extends Seeder
{
    private const INSIDE = 'เปิดได้เฉพาะเครือข่ายภายในโรงพยาบาล';

    /** [title, url, heading, note] */
    private const LINKS = [
        ['ตู้เอกสาร (รพ.)', 'http://10.55.110.10/doc/index.php?r=site/login', 'ระบบงานภายใน', self::INSIDE],
        ['ตู้เอกสาร (รพ.สต.)', 'http://203.157.195.34/doc/index.php?r=site/login', 'ระบบงานภายใน', null],
        ['P4P', 'http://10.55.107.3/p4p', 'ระบบงานภายใน', self::INSIDE],
        ['ระบบแจ้งซ่อมบำรุง', 'https://buachedhsp.moph.go.th/rp/', 'ระบบงานภายใน', null],
        ['งานพัฒนาบุคลากร (HRD)', 'http://61.19.69.67/hello/officer.html', 'ระบบงานภายใน', null],
        ['ตัวชี้วัดโรงพยาบาลบัวเชด', 'https://buachedhsp.moph.go.th/kpi_bchsp/dashboard/', 'ข้อมูลและตัวชี้วัด', null],
        ['HOSxP Dashboard', 'https://erp.buachedhsp.moph.go.th/app/user/dashboard-patient', 'ข้อมูลและตัวชี้วัด', null],
        ['Active case (คลินิกใจสบาย)', 'https://buachedhsp.moph.go.th/active_case/', 'ข้อมูลและตัวชี้วัด', null],
        ['HDC สุรินทร์', 'https://hdc.moph.go.th/srn/public/main', 'ข้อมูลและตัวชี้วัด', null],
        ['สำนักงานสาธารณสุขจังหวัดสุรินทร์', 'https://surin.moph.go.th/SPHO/', 'หน่วยงานที่เกี่ยวข้อง', null],
        ['สำนักงานสาธารณสุขอำเภอบัวเชด', 'https://buachedpho.moph.go.th', 'หน่วยงานที่เกี่ยวข้อง', null],
        ['NonHR', 'https://nonhr.moph.go.th/', 'หน่วยงานที่เกี่ยวข้อง', null],
        ['ระบบสุขภาพทางไกล รพ.สุรินทร์', 'https://surintelehealth.com/telehealth/authentication/login', 'หน่วยงานที่เกี่ยวข้อง', null],
        ['IS-Online', 'https://pher.moph.go.th/pher/service.html', 'หน่วยงานที่เกี่ยวข้อง', null],
    ];

    public function run(): void
    {
        if (StaffLink::exists()) {
            return;
        }

        foreach (self::LINKS as $order => [$title, $url, $group, $note]) {
            StaffLink::create(['title' => $title, 'url' => $url, 'group' => $group, 'note' => $note, 'sort_order' => $order + 1]);
        }
    }
}
