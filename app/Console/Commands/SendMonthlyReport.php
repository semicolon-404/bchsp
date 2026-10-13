<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Mail\MonthlyReport;
use App\Models\User;
use App\Support\Reports;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * On the first of each month: the report of the month before, by email to the administrators
 * (routes/console.php). The same report as the panel's Reports page shows. Says so and sends nothing
 * when no administrator has an email address.
 */
class SendMonthlyReport extends Command
{
    protected $signature = 'report:monthly {--if-first : Send only when today is the first of the month}';

    protected $description = 'Email the website report of the month before to the administrators';

    public function handle(): int
    {
        if ($this->option('if-first') && now()->day !== 1) {
            return self::SUCCESS;
        }

        $to = User::where('role', Role::Admin)->whereNotNull('email')->where('email', '!=', '')->pluck('email')->all();

        if ($to === []) {
            $this->warn('No administrator has an email address to send the report to.');

            return self::SUCCESS;
        }

        $from = now()->subMonthNoOverflow()->startOfMonth();

        try {
            Mail::to($to)->send(new MonthlyReport(Reports::summary($from, $from->copy()->endOfMonth()), $from));
        } catch (Throwable $e) {
            $this->error('The email could not be sent: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Report sent to '.count($to).' administrator(s).');

        return self::SUCCESS;
    }
}
