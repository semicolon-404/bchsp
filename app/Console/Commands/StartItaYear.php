<?php

namespace App\Console\Commands;

use App\Models\ItaItem;
use Illuminate\Console\Command;

/**
 * Starts a fiscal year of the ITA from its written-out list of items (database/data/ita-{year}.php): the headings,
 * items and their parts, with no evidence yet. Adds nothing to a year that already has items.
 */
class StartItaYear extends Command
{
    protected $signature = 'ita:template {year : The Buddhist fiscal year, e.g. 2569}';

    protected $description = 'Start a fiscal year of the ITA from its list of items and parts';

    public function handle(): int
    {
        $year = (int) $this->argument('year');
        $file = database_path("data/ita-$year.php");

        if (! is_file($file)) {
            $this->error("There is no list of items for $year (database/data/ita-$year.php).");

            return self::FAILURE;
        }

        if (ItaItem::where('year', $year)->exists()) {
            $this->warn("$year already has items: nothing was added.");

            return self::SUCCESS;
        }

        $items = require $file;

        foreach ($items as [$number, $category, $title, $parts]) {
            ItaItem::create([
                'year' => $year,
                'number' => $number,
                'category' => $category,
                'title' => $title,
                'parts' => array_map(fn (array $part) => ['round' => $part[0], 'title' => $part[1]], $parts) ?: null,
            ]);
        }

        $this->info(count($items)." items made for $year.");

        return self::SUCCESS;
    }
}
