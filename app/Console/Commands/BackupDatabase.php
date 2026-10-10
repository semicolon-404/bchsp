<?php

namespace App\Console\Commands;

use App\Support\Backups;
use Illuminate\Console\Command;
use Throwable;

/**
 * The nightly copy of the database and of the uploaded files (App\Support\Backups).
 * Run by the scheduler (routes/console.php); can be run by hand at any time.
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Copy the database and the uploaded files to storage/app/backups, and remove copies older than the days kept';

    public function handle(): int
    {
        try {
            $file = Backups::run();
            $uploads = Backups::mirrorUploads();
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Database copied to '.$file);
        $this->info("Uploaded files: {$uploads['copied']} copied, {$uploads['removed']} old ones removed.");

        return self::SUCCESS;
    }
}
