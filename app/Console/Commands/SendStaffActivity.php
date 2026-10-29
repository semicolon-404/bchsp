<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Mail\StaffActivityDigest;
use App\Models\User;
use App\Support\StaffActivity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Every morning: what each account did in the panel yesterday, by email to the administrators (routes/console.php).
 * Sends nothing on a day nothing was done, or when no administrator has an email address.
 */
class SendStaffActivity extends Command
{
    protected $signature = 'activity:daily';

    protected $description = 'Email the administrators what was done in the panel yesterday';

    public function handle(): int
    {
        $day = now()->subDay();
        $lines = StaffActivity::on($day);

        if ($lines->isEmpty()) {
            $this->info('Nothing was done yesterday.');

            return self::SUCCESS;
        }

        $to = User::where('role', Role::Admin)->whereNotNull('email')->where('email', '!=', '')->pluck('email')->all();

        if ($to === []) {
            $this->warn('No administrator has an email address to send it to.');

            return self::SUCCESS;
        }

        try {
            Mail::to($to)->send(new StaffActivityDigest($lines, $day));
        } catch (Throwable $e) {
            $this->error('The email could not be sent: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info($lines->count().' line(s) sent to '.count($to).' administrator(s).');

        return self::SUCCESS;
    }
}
