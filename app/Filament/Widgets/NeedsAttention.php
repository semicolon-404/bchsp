<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\SystemStatus;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Resources\ItaItems\ItaItemResource;
use App\Models\Announcement;
use App\Support\Reports;
use App\Support\SiteSettings;
use App\Support\SystemHealth;
use Filament\Widgets\Widget;

/**
 * What is waiting for someone, on the panel's first page: drafts nobody
 * published, announcements about to end, ITA items without documents, a notice
 * left switched on. Each line leads to where it is dealt with; nothing to do
 * shows as one quiet line.
 */
class NeedsAttention extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.needs-attention';

    /** @return list<array{text: string, url: ?string, tone: string}> */
    public function getItems(): array
    {
        $items = [];
        $add = function (int $count, string $text, ?string $url, string $tone = 'warning') use (&$items) {
            if ($count > 0) {
                $items[] = ['text' => trans_choice($text, $count, ['count' => $count]), 'url' => $url, 'tone' => $tone];
            }
        };

        if (AnnouncementResource::forTheWholeHospital()) {
            $add(Announcement::where('status', Announcement::STATUS_PENDING)->count(), '{1} :count post from a department is waiting for approval|[2,*] :count posts from departments are waiting for approval', AnnouncementResource::getUrl('index'), 'danger');
        }

        $add(Announcement::current()->whereNotNull('ends_at')->where('ends_at', '<=', now()->addDays(7))->count(), '{1} :count announcement ends within a week|[2,*] :count announcements end within a week', AnnouncementResource::getUrl('index'), 'info');
        $add(Announcement::where('status', Announcement::STATUS_PUBLISHED)->where('starts_at', '>', now())->count(), '{1} :count announcement is waiting for its date|[2,*] :count announcements are waiting for their dates', AnnouncementResource::getUrl('index'), 'info');

        $ita = Reports::ita(Announcement::fiscalYearOf(now()));
        $add($ita['items'] - $ita['with_evidence'], '{1} :count ITA item of this year has no documents yet|[2,*] :count ITA items of this year have no documents yet', ItaItemResource::getUrl('index'));

        if (SiteSettings::get('alert.enabled')) {
            $items[] = ['text' => __('The urgent notice is switched on: ":text"', ['text' => (string) SiteSettings::get('alert.text')]), 'url' => auth()->user()?->isAdmin() ? \App\Filament\Pages\SiteSettings::getUrl() : null, 'tone' => 'info'];
        }

        if (auth()->user()?->isAdmin()) {
            $add(SystemHealth::problems(), '{1} :count thing is wrong on System status|[2,*] :count things are wrong on System status', SystemStatus::getUrl(), 'danger');
        }

        return $items;
    }
}
