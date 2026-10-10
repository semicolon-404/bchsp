<?php

namespace App\Filament\Widgets;

use App\Support\Visits;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

/** Pages of the site opened on each of the last thirty days. */
class VisitsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    public function getHeading(): string|Htmlable|null
    {
        return __('Pages opened, the last 30 days');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = Visits::daily(now()->subDays(29), now());

        return [
            'datasets' => [[
                'label' => __('Pages opened'),
                'data' => array_values($days),
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => array_map(fn (string $day) => Carbon::parse($day)->format('j/n'), array_keys($days)),
        ];
    }
}
