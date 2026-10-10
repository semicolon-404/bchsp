<?php

namespace App\Console\Commands;

use App\Support\DemoData;
use Illuminate\Console\Command;

/**
 * Fills the site with sample content (App\Support\DemoData), to see every part
 * of it with something in it. Refuses on a live site unless told to go ahead:
 * visitors would read "[ตัวอย่าง]" announcements as real.
 */
class FillDemo extends Command
{
    protected $signature = 'demo:fill {--force : Go ahead even on a live site}';

    protected $description = 'Put sample content into the site (announcements, slides, articles, ITA, visits); "demo:clear" takes it out again';

    public function handle(): int
    {
        if (DemoData::isFilled()) {
            $this->warn('The sample content is already in. Run "php artisan demo:clear" first to start over.');

            return self::FAILURE;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('This is a live site (APP_ENV=production). Sample content would be shown to the public. Add --force if that is really wanted.');

            return self::FAILURE;
        }

        foreach ((new DemoData)->fill() as $what => $count) {
            $this->line(str_pad((string) $count, 6, ' ', STR_PAD_LEFT).'  '.class_basename($what));
        }

        $this->info('Sample content is in. Everything is named "'.DemoData::MARK.'"; "php artisan demo:clear" removes it.');

        return self::SUCCESS;
    }
}
