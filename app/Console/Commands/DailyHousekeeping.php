<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\LinkCheck;
use App\Support\LinkChecks;
use App\Support\PostNotices;
use App\Support\Searches;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The small work of every night, in one run (routes/console.php, and App\Support\WebCron where the server
 * has no scheduler):
 * the bin is emptied of announcements deleted more than thirty days ago, old search words are dropped,
 * whoever wrote an announcement that ends within three days is told, notifications read over ninety days ago
 * are cleared, and a batch of the links to files kept
 * elsewhere is looked at.
 */
class DailyHousekeeping extends Command
{
    protected $signature = 'site:daily {--links=30 : How many links to look at in this run}';

    protected $description = 'Empty the bin, drop old search words, tell of announcements about to end, and check a batch of links';

    public function handle(): int
    {
        $this->info('Emptied from the bin: '.Announcement::emptyBin());
        $this->info('Old search words dropped: '.Searches::prune());
        $this->info('Told of announcements about to end: '.PostNotices::tellOfEndings());
        $this->info('Old read notifications cleared: '.DB::table('notifications')->whereNotNull('read_at')->where('read_at', '<', now()->subDays(90))->delete());

        $looked = LinkChecks::run(max(0, (int) $this->option('links')));
        $this->info('Links looked at: '.$looked.' (gone or not shared: '.LinkCheck::whereIn('state', [LinkCheck::DEAD, LinkCheck::PRIVATE])->count().')');

        return self::SUCCESS;
    }
}
