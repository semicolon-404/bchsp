<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * The departments listed on the hospital's previous website, as a starting
 * point: names and the hours that site gave. Run once on a new installation
 * (php artisan db:seed); it adds nothing when departments already exist, so
 * what staff have since changed in the panel is never overwritten.
 */
class DepartmentSeeder extends Seeder
{
    private const DEPARTMENTS = [
        ['กลุ่มงานบริหารทั่วไป', 'General Administration', null],
        ['งานอุบัติเหตุ-ฉุกเฉิน', 'Accident and Emergency', 'เปิดให้บริการตลอด 24 ชั่วโมง ทุกวันไม่เว้นวันหยุด'],
        ['งานผู้ป่วยนอก (OPD)', 'Outpatient Department', 'ตรวจโรคทั่วไป ทุกวันไม่เว้นวันหยุดราชการ เวลา 08.00–16.00 น.'],
        ['งานผู้ป่วยใน (IPD)', 'Inpatient Department', null],
        ['กลุ่มงานเภสัชกรรม', 'Pharmacy', null],
        ['กลุ่มงานทันตกรรม', 'Dental', null],
        ['งานบริการปฐมภูมิ (PCU)', 'Primary Care Unit', null],
        ['งานเวชระเบียน', 'Medical Records', null],
        ['งานแพทย์แผนไทย', 'Thai Traditional Medicine', 'วันจันทร์–ศุกร์ เวลา 08.00–16.00 น. และ 16.00–20.00 น. วันหยุดนักขัตฤกษ์ เวลา 08.00–16.00 น.'],
        ['งานห้องคลอด', 'Labour Room', 'เปิดให้บริการตลอด 24 ชั่วโมง ทุกวันไม่เว้นวันหยุด'],
        ['งานจ่ายกลาง', 'Central Sterile Supply', null],
        ['กลุ่มงานจิตเวชและยาเสพติด', 'Psychiatry and Substance Use', null],
    ];

    public function run(): void
    {
        if (Department::exists()) {
            return;
        }

        foreach (self::DEPARTMENTS as $order => [$name, $english, $hours]) {
            Department::create(['name' => $name, 'name_en' => $english, 'hours' => $hours, 'sort_order' => $order + 1]);
        }
    }
}
