<?php

namespace App\Filament\Widgets;

use App\Models\Announcement;
use App\Support\Visits;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The figures at the top of the panel's first page: how much the site is being
 * read, and how much has been published this month.
 */
class SiteStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totals = Visits::totals();
        $lastMonth = Visits::between(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth());
        // The last seven days, as the small line under the first figure.
        $week = array_values(Visits::daily(now()->subDays(6), now()));

        return [
            Stat::make(__('Pages opened today'), number_format($totals['today']))
                ->description(__('The last seven days'))
                ->chart($week)
                ->color('primary'),
            Stat::make(__('Pages opened this month'), number_format($totals['month']))
                ->description(__('Last month: :count', ['count' => number_format($lastMonth)])),
            Stat::make(__('Announcements this month'), number_format(Announcement::published()->where('starts_at', '>=', now()->startOfMonth())->count()))
                ->description(__('In all: :count', ['count' => number_format(Announcement::published()->count())])),
            Stat::make(__('Showing now'), number_format(Announcement::current()->count()))
                ->description(__('On the slides: :count', ['count' => number_format(Announcement::slides()->count())])),
        ];
    }
}
