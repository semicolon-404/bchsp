<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * Every notification of the one signed in, on a page of its own: the bell's side panel holds only the newest.
 * Grouped by day, narrowed by unread and by what it is about (read from its link and icon), searched by its words; thirty
 * at a time with more on asking. Marked read as it is opened; all read, or the read ones cleared, in a press.
 */
class Notifications extends Page
{
    /** What a notification is about => [what the page calls it, what its stored data holds (all must match)]. */
    public const KINDS = [
        // Links are stored with their slashes escaped: the part's name is matched on its own.
        'posts' => ['Announcements', ['%announcements%'], ['%"icon":"heroicon-o-chat-bubble-left-right"%']],
        'comments' => ['Comments', ['%"icon":"heroicon-o-chat-bubble-left-right"%', '%announcements%'], []],
        'complaints' => ['Complaints', ['%complaints%'], []],
        'system' => ['System', ['%"icon":"heroicon-o-shield-exclamation"%'], []],
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.pages.notifications';

    #[Url]
    public string $show = 'all';

    /** Which kind one is, for its colour: the first whose words its stored data holds. */
    public static function kindOf(DatabaseNotification $one): string
    {
        $data = json_encode($one->data);

        foreach (self::KINDS as $key => [, $with, $without]) {
            $holds = fn (string $like) => str_contains($data, trim($like, '%'));

            if (collect($with)->every($holds) && ! collect($without)->contains($holds)) {
                return $key;
            }
        }

        return 'other';
    }

    #[Url]
    public string $q = '';

    public int $limit = 30;

    public static function getNavigationLabel(): string
    {
        return __('Notifications');
    }

    public function getTitle(): string
    {
        return __('Notifications');
    }

    /** How many are unread, beside the menu entry. */
    public static function getNavigationBadge(): ?string
    {
        $count = auth()->user()?->unreadNotifications()->count();

        return $count ? (string) $count : null;
    }

    private function query()
    {
        $query = auth()->user()->notifications()->getQuery();

        if ($this->show === 'unread') {
            $query->whereNull('read_at');
        } elseif (isset(self::KINDS[$this->show])) {
            [, $with, $without] = self::KINDS[$this->show];
            foreach ($with as $like) {
                $query->where('data', 'like', $like);
            }
            foreach ($without as $like) {
                $query->where('data', 'not like', $like);
            }
        }

        if (mb_strlen(trim($this->q)) >= 2) {
            $query->where('data', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], trim($this->q)).'%');
        }

        return $query;
    }

    /** @return array{groups: Collection, more: bool, counts: array<string, int>} */
    public function getViewData(): array
    {
        $items = $this->query()->latest()->limit($this->limit + 1)->get();
        $more = $items->count() > $this->limit;

        $groups = $items->take($this->limit)->groupBy(fn (DatabaseNotification $one) => match (true) {
            $one->created_at->isToday() => __('Today'),
            $one->created_at->isYesterday() => __('Yesterday'),
            $one->created_at->gte(now()->startOfWeek()) => __('This week'),
            default => __('Earlier'),
        });

        $mine = auth()->user()->notifications();

        return [
            'groups' => $groups,
            'more' => $more,
            'counts' => ['all' => (clone $mine)->count(), 'unread' => auth()->user()->unreadNotifications()->count()],
        ];
    }

    public function showMore(): void
    {
        $this->limit += 30;
    }

    public function updatedShow(): void
    {
        $this->limit = 30;
    }

    public function open(string $id): void
    {
        $one = auth()->user()->notifications()->find($id);

        if (! $one) {
            return;
        }

        $one->markAsRead();
        // Only an address on this site: what is stored is followed, never somewhere else.
        $url = $one->data['actions'][0]['url'] ?? null;
        $url = is_string($url) && parse_url($url, PHP_URL_PATH) && str_starts_with((string) parse_url($url, PHP_URL_PATH), '/') ? url(parse_url($url, PHP_URL_PATH).(parse_url($url, PHP_URL_QUERY) ? '?'.parse_url($url, PHP_URL_QUERY) : '')) : null;

        if ($url) {
            $this->redirect($url);
        }
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function clearRead(): void
    {
        auth()->user()->notifications()->whereNotNull('read_at')->delete();
    }
}
