<?php

use App\Models\AccessLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Drops access-log lines older than the retention period (config/bchsp.php).
Schedule::command('model:prune', ['--model' => [AccessLog::class]])->daily();
