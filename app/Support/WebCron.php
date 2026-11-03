<?php

namespace App\Support;

use App\Models\AccessLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * The site's daily work on a server that cannot run scheduled work at all: shared hosting with no
 * cron to set and no way to run a program (SCHEDULE_ON_VISITS=true in .env).
 *
 * There, a visit does it: after a page has been sent, at most once a minute, each daily job whose
 * hour has passed and which has not run today is run, inside PHP itself. The night's backup then
 * happens with the first visit after 03:10 rather than at 03:10. A site nobody opens for a day
 * skips that day: where a real scheduler can be set (docs/DEPLOY.th.md), it should be.
 */
class WebCron
{
    /** Job => [the hour it is due from, the command, its arguments]. The same work routes/console.php gives a real scheduler. */
    public const JOBS = [
        'prune' => ['00:30', 'model:prune', ['--model' => [AccessLog::class]]],
        'backup' => ['03:10', 'backup:database', []],
        'daily' => ['04:20', 'site:daily', ['--links' => 20]],
        'notify' => ['07:30', 'health:notify', []],
        'activity' => ['07:35', 'activity:daily', []],
        // Asked every day, sent on the first of the month only.
        'report' => ['08:00', 'report:monthly', ['--if-first' => true]],
    ];

    public static function isOn(): bool
    {
        return (bool) config('bchsp.schedule_on_visits');
    }

    /**
     * Run what is due. Returns the jobs that were run.
     *
     * @return list<string>
     */
    public static function tick(): array
    {
        $ran = [];

        try {
            // At most once a minute, whatever the number of visitors: the first one in takes the minute.
            if (! self::isOn() || ! Cache::add('webcron:tick', true, 55)) {
                return [];
            }

            // What System status reads as "scheduled work is running".
            SystemHealth::beat();

            $today = now()->toDateString();

            foreach (self::JOBS as $name => [$from, $command, $arguments]) {
                if (now()->format('H:i') < $from || Cache::get("webcron:$name") === $today) {
                    continue;
                }

                // Marked before it runs: a job that takes long is not started twice by the next visitor.
                Cache::forever("webcron:$name", $today);
                Artisan::call($command, $arguments);
                $ran[] = $name;
            }
        } catch (Throwable $e) {
            // A visitor's page was already sent; nothing here may undo that.
            report($e);
        }

        return $ran;
    }
}
