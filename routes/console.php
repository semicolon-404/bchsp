<?php

use App\Models\AccessLog;
use App\Support\SystemHealth;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Drops access-log lines older than the retention period (config/bchsp.php).
Schedule::command('model:prune', ['--model' => [AccessLog::class]])->daily();

// The small work of every night: the bin emptied, old search words dropped, authors told of announcements
// about to end, a batch of links looked at (App\Console\Commands\DailyHousekeeping).
Schedule::command('site:daily')->dailyAt('04:20')->withoutOverlapping();

// On the first of the month: the report of the month before, to the administrators.
Schedule::command('report:monthly')->monthlyOn(1, '08:00');

// A copy of the database and of the uploaded files every night, kept two weeks (App\Support\Backups).
// On this disk only: send it elsewhere too.
Schedule::command('backup:database')->dailyAt('03:10')->withoutOverlapping();

// Proof that scheduled work is running at all, shown on the panel's System status page.
Schedule::call(fn () => SystemHealth::beat())->everyMinute()->name('system:heartbeat');

// Every morning: an email to the administrators when something on System status is wrong (a backup that
// did not run, a disk nearly full), so it is noticed the next day and not the day it is needed.
Schedule::command('health:notify')->dailyAt('07:30');
