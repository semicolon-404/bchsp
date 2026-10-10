<?php

namespace App\Console\Commands;

use App\Support\DemoData;
use Illuminate\Console\Command;

/** Takes out the sample content that "demo:fill" put in, and only that (App\Support\DemoData). */
class ClearDemo extends Command
{
    protected $signature = 'demo:clear';

    protected $description = 'Remove the sample content that "demo:fill" put in; real content is not touched';

    public function handle(): int
    {
        if (! DemoData::isFilled()) {
            $this->info('There is no sample content to remove.');

            return self::SUCCESS;
        }

        $this->info(DemoData::clear().' sample records removed, with their pictures, files and visit counts.');

        return self::SUCCESS;
    }
}
