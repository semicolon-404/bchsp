<?php

namespace App\Console\Commands;

use App\Models\LinkCheck;
use App\Support\LinkChecks;
use Illuminate\Console\Command;

/** Looks at a batch of the announcements' links to files kept elsewhere (App\Support\LinkChecks). Run every night. */
class CheckLinks extends Command
{
    protected $signature = 'links:check {--limit=60 : How many links to look at in this run}';

    protected $description = 'Check that links to files kept elsewhere (Google Drive) still open';

    public function handle(): int
    {
        $looked = LinkChecks::run(max(1, (int) $this->option('limit')));

        $this->info($looked.' link(s) looked at. Gone: '.LinkCheck::where('state', LinkCheck::DEAD)->count().', not shared with everyone: '.LinkCheck::where('state', LinkCheck::PRIVATE)->count().'.');

        return self::SUCCESS;
    }
}
