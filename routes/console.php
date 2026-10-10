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

// A copy of the database and of the uploaded files every night, kept two weeks (App\Support\Backups).
// On this disk only: send it elsewhere too.
Schedule::command('backup:database')->dailyAt('03:10')->withoutOverlapping();

// Proof that scheduled work is running at all, shown on the panel's System status page.
Schedule::call(fn () => SystemHealth::beat())->everyMinute()->name('system:heartbeat');
