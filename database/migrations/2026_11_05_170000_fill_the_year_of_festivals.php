<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The year's days of note as Thai offices keep them, set out ahead in Festivals, from now to the end of 2570.
 * Days fixed in the calendar are on. Those that follow the moon (the Buddhist days, Chinese New Year, Loy Krathong)
 * move each year: they are put in on the day expected, off, and marked to check, for an administrator to set by the
 * government calendar and turn on.
 */
return new class extends Migration
{
    public function up(): void
    {
        $check = ' (ตรวจวันที่ก่อนเปิด)';
        // [name, theme, from, to, on]
        $days = [
            ['วันพ่อแห่งชาติ 2569', 'father', '2026-12-03', '2026-12-05', true],
            ['วันรัฐธรรมนูญ 2569', 'constitution', '2026-12-10', '2026-12-10', true],
            ['คริสต์มาส 2569', 'christmas', '2026-12-20', '2026-12-25', true],
            ['ส่งท้ายปีเก่า ต้อนรับปีใหม่ 2570', 'newyear', '2026-12-29', '2027-01-02', true],
            ['วันเด็กแห่งชาติ 2570', 'children', '2027-01-07', '2027-01-09', true],
            ['ตรุษจีน 2570'.$check, 'chinese', '2027-02-05', '2027-02-07', false],
            ['วันวาเลนไทน์ 2570', 'valentine', '2027-02-12', '2027-02-14', true],
            ['วันมาฆบูชา 2570'.$check, 'makha', '2027-02-20', '2027-02-21', false],
            ['วันจักรี 2570', 'chakri', '2027-04-06', '2027-04-06', true],
            ['สงกรานต์ 2570', 'songkran', '2027-04-12', '2027-04-16', true],
            ['วันวิสาขบูชา 2570'.$check, 'visakha', '2027-05-19', '2027-05-20', false],
            ['วันเฉลิมพระชนมพรรษาสมเด็จพระราชินี 2570', 'queen', '2027-06-01', '2027-06-03', true],
            ['วันอาสาฬหบูชาและวันเข้าพรรษา 2570'.$check, 'asalha', '2027-07-17', '2027-07-19', false],
            ['วันเฉลิมพระชนมพรรษา ร.10 2570', 'king', '2027-07-26', '2027-07-28', true],
            ['วันแม่แห่งชาติ 2570', 'mother', '2027-08-10', '2027-08-12', true],
            ['วันนวมินทรมหาราช 2570', 'mourning', '2027-10-13', '2027-10-13', true],
            ['วันออกพรรษา 2570'.$check, 'endlent', '2027-10-14', '2027-10-15', false],
            ['วันพยาบาลแห่งชาติ 2570', 'nurses', '2027-10-20', '2027-10-21', true],
            ['วันปิยมหาราช 2570', 'chulalongkorn', '2027-10-23', '2027-10-23', true],
            ['ลอยกระทง 2570'.$check, 'loykrathong', '2027-11-11', '2027-11-14', false],
            ['วันพ่อแห่งชาติ 2570', 'father', '2027-12-03', '2027-12-05', true],
            ['วันรัฐธรรมนูญ 2570', 'constitution', '2027-12-10', '2027-12-10', true],
            ['คริสต์มาส 2570', 'christmas', '2027-12-20', '2027-12-25', true],
        ];

        $now = Carbon::now();

        foreach ($days as [$name, $theme, $from, $to, $on]) {
            // Not twice: a day already set by hand is left as it is.
            if (DB::table('festivals')->where('theme', $theme)->whereDate('starts_on', $from)->exists()) {
                continue;
            }

            DB::table('festivals')->insert([
                'name' => $name, 'theme' => $theme, 'starts_on' => $from, 'ends_on' => $to,
                'intensity' => $theme === 'mourning' ? 'low' : 'medium', 'is_active' => $on,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // The days are the administrators' once in: left in place.
    }
};
