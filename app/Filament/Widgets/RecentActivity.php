<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\AccessLogs\AccessLogResource;
use App\Models\AccessLog;
use App\Support\StaffActivity;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/** The last things done in the panel, by anyone, on the administrators' first page. Read off the access log. */
class RecentActivity extends Widget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.recent-activity';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    /** @return Collection<int, AccessLog> */
    public function getLines(): Collection
    {
        return StaffActivity::latest();
    }

    public function getAllUrl(): string
    {
        return AccessLogResource::getUrl('index');
    }
}
