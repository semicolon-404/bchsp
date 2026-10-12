<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Mail\SystemProblems;
use App\Models\User;
use App\Support\SystemHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tells the administrators by email when something on System status is wrong, so that a backup that
 * stopped running is noticed the next morning and not the day it is needed. Run every morning
 * (routes/console.php). Says nothing when all is well, and nothing when no administrator has an email
 * address or the server cannot send mail: the red number in the panel's menu is still there.
 */
class NotifyOfProblems extends Command
{
    protected $signature = 'health:notify';

    protected $description = 'Email the administrators when something on System status is wrong';

    public function handle(): int
    {
        $problems = array_values(array_filter(SystemHealth::checks(), fn (array $check) => $check['state'] === 'problem'));

        if ($problems === []) {
            $this->info('Nothing is wrong.');

            return self::SUCCESS;
        }

        $to = User::where('role', Role::Admin)->whereNotNull('email')->where('email', '!=', '')->pluck('email')->all();

        if ($to === []) {
            $this->warn(count($problems).' problem(s), but no administrator has an email address to tell.');

            return self::SUCCESS;
        }

        try {
            Mail::to($to)->send(new SystemProblems($problems));
        } catch (Throwable $e) {
            $this->error('The email could not be sent: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(count($problems).' problem(s) sent to '.count($to).' administrator(s).');

        return self::SUCCESS;
    }
}
