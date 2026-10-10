<?php

namespace Database\Seeders;

use App\Models\ClinicSession;
use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * The opening hours the hospital's previous website gave, as a starting point
 * for the schedule. Run once on a new installation (php artisan db:seed); it
 * adds nothing when the schedule already has something in it. Staff should
 * check every line against today's real hours before the site goes live.
 */
class ClinicSessionSeeder extends Seeder
{
    private const EVERY_DAY = [0, 1, 2, 3, 4, 5, 6];

    private const WEEKDAYS = [1, 2, 3, 4, 5];

    /** [name, English name, department's name, days, from, to, note] */
    private const SESSIONS = [
        ['อุบัติเหตุ-ฉุกเฉิน', 'Accident and Emergency', 'งานอุบัติเหตุ-ฉุกเฉิน', self::EVERY_DAY, '00:00', '23:59', 'โทรฉุกเฉิน 1669'],
        ['ห้องคลอด', 'Labour Room', 'งานห้องคลอด', self::EVERY_DAY, '00:00', '23:59', null],
        ['ตรวจโรคทั่วไป', 'General outpatient clinic', 'งานผู้ป่วยนอก (OPD)', self::EVERY_DAY, '08:00', '16:00', 'ไม่เว้นวันหยุดราชการ'],
        ['ทันตกรรม (เช้า)', 'Dental (morning)', 'กลุ่มงานทันตกรรม', self::EVERY_DAY, '08:15', '11:30', null],
        ['ทันตกรรม (บ่าย)', 'Dental (afternoon)', 'กลุ่มงานทันตกรรม', [0, 1, 2, 3, 4, 6], '13:00', '15:30', null],
        ['แพทย์แผนไทย', 'Thai Traditional Medicine', 'งานแพทย์แผนไทย', self::WEEKDAYS, '08:00', '16:00', 'นวด ประคบ อบสมุนไพร'],
        ['แพทย์แผนไทย (นอกเวลา)', 'Thai Traditional Medicine (evening)', 'งานแพทย์แผนไทย', self::WEEKDAYS, '16:00', '20:00', null],
    ];

    public function run(): void
    {
        if (ClinicSession::exists()) {
            return;
        }

        foreach (self::SESSIONS as $order => [$name, $english, $department, $days, $from, $to, $note]) {
            ClinicSession::create([
                'name' => $name, 'name_en' => $english,
                'department_id' => Department::where('name', $department)->value('id'),
                'days' => $days, 'starts_at' => $from, 'ends_at' => $to, 'note' => $note, 'sort_order' => $order + 1,
            ]);
        }
    }
}
