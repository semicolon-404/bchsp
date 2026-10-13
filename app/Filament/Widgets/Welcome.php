<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Guide;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Announcement;
use Filament\Widgets\Widget;

/**
 * The top of the panel's first page: a greeting by name, who the account is (an
 * administrator, an author, or the account of one department), and the few
 * things most people come here to do, one press away.
 */
class Welcome extends Widget
{
    protected static ?int $sort = -1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.welcome';

    public function getGreeting(): string
    {
        $hour = (int) now(config('app.display_timezone', 'Asia/Bangkok'))->format('G');

        return __(match (true) {
            $hour < 12 => 'Good morning, :name',
            $hour < 17 => 'Good afternoon, :name',
            default => 'Good evening, :name',
        }, ['name' => auth()->user()->name]);
    }

    /** What the account is, in a word; and its department, when it is the account of one. */
    public function getRole(): string
    {
        return __(auth()->user()->isAdmin() ? 'Administrator' : 'Author');
    }

    public function getDepartment(): ?string
    {
        $user = auth()->user();

        return $user->postsForOneDepartment() ? $user->department?->localName() : null;
    }

    /**
     * The things to do from here, the first of them the one most people came for.
     *
     * @return list<array{label: string, note: string, url: string, icon: string, new: bool, count: int}>
     */
    public function getDoors(): array
    {
        $user = auth()->user();
        $door = fn (string $label, string $note, string $url, string $icon, bool $new = false, int $count = 0) => compact('label', 'note', 'url', 'icon', 'new', 'count');

        $doors = [$door(__('Write a new post'), __('News, a poster, an album or a document'), AnnouncementResource::getUrl('create'), 'heroicon-o-pencil-square')];

        if ($user->postsForOneDepartment()) {
            $doors[] = $door(__('My posts'), __('What this department has posted'), AnnouncementResource::getUrl('index'), 'heroicon-o-queue-list');

            if ($user->department) {
                $doors[] = $door(__('The department\'s profile'), __('Picture, services, people, contacts'), DepartmentResource::getUrl('index'), 'heroicon-o-identification');
                $doors[] = $door(__('See the page as visitors do'), $user->department->localName(), route('departments.show', $user->department), 'heroicon-o-arrow-top-right-on-square', true);
            }
        } else {
            $waiting = Announcement::where('status', Announcement::STATUS_PENDING)->count();

            $doors[] = $waiting > 0
                ? $door(__('Posts waiting for approval'), __('Sent in by the departments'), AnnouncementResource::getUrl('index'), 'heroicon-o-check-badge', count: $waiting)
                : $door(__('All announcements'), __('Edit, pin, put on the slides'), AnnouncementResource::getUrl('index'), 'heroicon-o-queue-list');
            $doors[] = $door(__('Open the website'), parse_url(url('/'), PHP_URL_HOST) ?: url('/'), url('/'), 'heroicon-o-arrow-top-right-on-square', true);
        }

        $doors[] = $door(__('How to use the panel'), __('A short guide, with pictures of each step'), Guide::getUrl(), 'heroicon-o-book-open');

        return $doors;
    }
}
