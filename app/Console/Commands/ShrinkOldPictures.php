<?php

namespace App\Console\Commands;

use App\Support\OldPictures;
use Illuminate\Console\Command;

/**
 * Makes the pictures kept thousands of dots wide smaller where they lie (App\Support\OldPictures), each original kept
 * first in storage/app/private/picture-originals. Run by hand, when the site's keeper chooses.
 */
class ShrinkOldPictures extends Command
{
    protected $signature = 'site:shrink-pictures {--at-most=200 : How many pictures to make smaller in this run}';

    protected $description = 'Make old pictures wider than 1920 dots smaller in place, keeping each original';

    public function handle(): int
    {
        $this->info('Old pictures made smaller: '.OldPictures::shrink(max(1, (int) $this->option('at-most'))));
        $this->line('Originals kept in storage/app/private/'.OldPictures::ORIGINALS);

        return self::SUCCESS;
    }
}
