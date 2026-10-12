<?php

namespace App\Filament\Widgets;

use App\Models\Announcement;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * For the account of a department, at the top of the panel's first page: how its own
 * posts are doing. Nobody else sees it.
 */
class DepartmentStats extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->postsForOneDepartment();
    }

    protected function getHeading(): ?string
    {
        return auth()->user()->department?->name;
    }

    protected function getStats(): array
    {
        $own = fn () => Announcement::query()->where('department_id', auth()->user()->department_id);
        $most = $own()->published()->where('views', '>', 0)->orderByDesc('views')->first();

        return [
            Stat::make(__('Posts showing now'), number_format($own()->current()->count()))
                ->description(__('In all: :count', ['count' => number_format($own()->published()->count())])),
            Stat::make(__('Times our posts were opened'), number_format((int) $own()->sum('views')))
                ->description($most ? __('Most read: :title', ['title' => str($most->title)->limit(40)]) : __('Nothing has been opened yet')),
            Stat::make(__('Waiting for approval'), number_format($own()->where('status', Announcement::STATUS_PENDING)->count()))
                ->description(__('Sent back: :back · drafts: :drafts', ['back' => number_format($own()->where('status', Announcement::STATUS_RETURNED)->count()), 'drafts' => number_format($own()->where('status', Announcement::STATUS_DRAFT)->count())]))
                ->color('warning'),
        ];
    }
}
