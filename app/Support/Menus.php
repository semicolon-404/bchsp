<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\Faq;
use App\Models\ItaItem;
use App\Models\SitePage;
use App\Models\StaffLink;
use Illuminate\Support\Facades\Route;

/**
 * The site's menus: the bar at the top of a wide screen, the dock at the bottom
 * of a phone or tablet, and the page that lists everything (/menu).
 *
 * The public menu is kept to what a government hospital's site is expected to
 * show, in groups:
 *
 *   Home · About us · ITA · News · Procurement
 *
 * "About us" holds the pages an administrator filed there (System > Pages),
 * the departments and the contact page; "ITA" holds its fiscal years and the
 * pages filed there. Both are too long to drop from the bar: each leads to a
 * page of its own (/about, /ita) that lists what it holds. "News" opens onto the kinds of
 * announcement, the articles and the downloads. What staff use (their own
 * services) is not in the public menu at all: it has a page of its own, /staff.
 *
 * An administrator chooses which groups are in the bar and in the dock, and in
 * what order (System > Site settings > "Menus"). Until one is changed nothing
 * is saved and the menu is what it always was (MENUS below).
 */
class Menus
{
    /** The kind of announcement that has a place of its own in the menu. */
    public const PROCUREMENT = 'procurement';

    /**
     * Place => [label, short label for a tab, heroicon]. Where each leads, and what it opens onto, is in place().
     */
    public const PLACES = [
        'home' => ['Home', 'Home', 'home'],
        'about' => ['About us', 'About', 'information-circle'],
        'ita' => ['ITA', 'ITA', 'shield-check'],
        'news' => ['News', 'Updates', 'megaphone'],
        'procurement' => ['Procurement', 'Bids', 'clipboard-document-list'],
        'schedule' => ['Clinic schedule', 'Schedule', 'calendar-days'],
        'departments' => ['Departments', 'Departments', 'building-office-2'],
        'downloads' => ['Downloads', 'Downloads', 'arrow-down-tray'],
        'contact' => ['Contact us', 'Contact', 'phone'],
        'staff' => ['For staff', 'Staff', 'identification'],
        // The page that lists everything: how a phone reaches what its dock has no room for.
        'menu' => ['Menu', 'Menu', 'bars-3'],
    ];

    /** Menu => [setting, what it holds when untouched, how many it can hold]. */
    private const MENUS = [
        'top' => ['menu.top', ['home', 'about', 'ita', 'news', 'procurement'], 7],
        // Five: more than that and a thumb misses.
        'tabs' => ['menu.tabs', ['home', 'news', 'procurement', 'ita', 'menu'], 5],
        // Everything the public comes for, for the page that lists it all. Not arranged by anyone.
        'all' => [null, ['home', 'about', 'ita', 'news', 'procurement'], 99],
    ];

    public static function max(string $menu): int
    {
        return self::MENUS[$menu][2];
    }

    /**
     * The menu as it should be drawn.
     *
     * @return list<array{key: string, label: string, short: string, icon: string, url: string, active: bool, opens: bool, children: list<array{label: string, url: string, active: bool}>}>
     */
    public static function items(string $menu): array
    {
        $items = [];

        foreach (self::keys($menu) as $key) {
            if ($place = self::place($key)) {
                [$label, $short, $icon] = self::PLACES[$key];

                // "opens": whether the top bar drops a list from this group. The page that lists everything shows the children either way.
                $items[] = compact('key', 'label', 'short', 'icon') + $place + ['opens' => $place['children'] !== []];
            }
        }

        // The administrator's own links come after the site's groups, in the bar and on the page that lists everything.
        if ($menu !== 'tabs') {
            foreach (self::ownLinks('top') as $index => $own) {
                $items[] = ['key' => 'own-'.$index, 'label' => $own['label'], 'short' => $own['label'], 'icon' => 'link', 'url' => $own['url'], 'active' => false, 'children' => [], 'opens' => false, 'own' => true];
            }
        }

        return $items;
    }

    /**
     * Where a place leads, whether the visitor is in it right now, and what it opens onto.
     * Null for a place that has nothing to show (its page taken out of the site, no procurement type).
     *
     * @return array{url: string, active: bool, children: list<array{label: string, url: string, active: bool}>}|null
     */
    /** Whether there are online services to list: asked by the menu, the footer and the home page, once a request. */
    public static function hasServices(): bool
    {
        return self::once('menus.services', fn () => StaffLink::forThePublic()->exists());
    }

    /** Whether there are questions people ask to list: as hasServices. */
    public static function hasFaq(): bool
    {
        return self::once('menus.faq', fn () => Faq::shown()->exists());
    }

    /** Forgets what this request worked out: for code that changes what the menus list and reads them again at once. */
    public static function forget(): void
    {
        foreach (array_keys(request()->attributes->all()) as $key) {
            if (str_starts_with($key, 'menus.')) {
                request()->attributes->remove($key);
            }
        }
    }

    /** What one request has worked out already, kept on it (and so never carried to the next). */
    private static function once(string $key, \Closure $work): mixed
    {
        $attributes = request()->attributes;

        if (! $attributes->has($key)) {
            $attributes->set($key, $work());
        }

        return $attributes->get($key);
    }

    private static function place(string $key): ?array
    {
        // Asked for by the bar, the dock and the menu page of one request: worked out once for it.
        return self::once('menus.place.'.$key, fn () => self::build($key));
    }

    private static function build(string $key): ?array
    {
        $request = request();
        $type = $request->routeIs('announcements.index') ? $request->query('type') : null;
        $link = fn (string $label, string $url, bool $active = false) => compact('label', 'url', 'active');
        $simple = fn (string $route, string $pattern) => Route::has($route)
            ? ['url' => route($route), 'active' => $request->routeIs($pattern), 'children' => []]
            : null;

        switch ($key) {
            case 'about':
                $children = SitePage::menuLinks('about')
                    ->map(fn (SitePage $page) => $link($page->localTitle(), route('pages.show', $page), $request->routeIs('pages.show') && $request->route('page')?->is($page)))
                    ->all();
                // What patients look for, each listed once it has something in it.
                if (self::hasServices()) {
                    $children[] = $link(__('Services online'), route('services'), $request->routeIs('services'));
                }

                if (self::hasFaq()) {
                    $children[] = $link(__('Questions people ask'), route('faq'), $request->routeIs('faq'));
                }

                $children[] = $link(__('Clinic schedule'), route('schedule'), $request->routeIs('schedule'));
                $children[] = $link(__('Departments'), route('departments.index'), $request->routeIs('departments.*'));
                $children[] = $link(__('Contact us'), route('contact'), $request->routeIs('contact'));

                foreach (self::ownLinks('about') as $own) {
                    $children[] = $link($own['label'], $own['url']);
                }

                // Too many to drop from the bar: the bar's link leads to a page that lists them (/about).
                return ['url' => route('about'), 'active' => $request->routeIs('about') || in_array(true, array_column($children, 'active'), true), 'children' => $children, 'opens' => false];

            case 'ita':
                $shown = $request->routeIs('ita') ? (int) ($request->route('year') ?? (ItaItem::years()[0] ?? 0)) : null;
                $children = array_map(
                    fn (int $year, int $index) => $link(__('Fiscal year :year', ['year' => $year]), route('ita', $index === 0 ? [] : ['year' => $year]), $shown === $year),
                    ItaItem::years(), array_keys(ItaItem::years()),
                );

                foreach (SitePage::menuLinks('ita') as $page) {
                    $children[] = $link($page->localTitle(), route('pages.show', $page), $request->routeIs('pages.show') && $request->route('page')?->is($page));
                }

                return [
                    'url' => route('ita'),
                    'active' => $request->routeIs('ita') || in_array(true, array_column($children, 'active'), true),
                    // One year and nothing else is just the page itself: nothing to list.
                    'children' => count($children) > 1 ? $children : [],
                    // The ITA page itself offers the years and the pages: the bar's link leads straight to it.
                    'opens' => false,
                ];

            case 'news':
                $children = AnnouncementType::where('slug', '!=', self::PROCUREMENT)->orderBy('sort_order')->orderBy('name')->get()
                    ->map(fn (AnnouncementType $one) => $link($one->localName(), route('announcements.index', ['type' => $one->slug]), $type === $one->slug))
                    ->all();
                // An administrator can leave these out of the list (Site settings > Menus).
                // The feed, once a department has posted.
                if (Announcement::published()->whereNotNull('department_id')->exists()) {
                    $children[] = $link(__('Feed of the departments'), route('posts'), $request->routeIs('posts'));
                }

                // The calendar, once anything has been given a day.
                if (Announcement::hasEvents()) {
                    $children[] = $link(__('Calendar'), route('calendar'), $request->routeIs('calendar'));
                }

                if (! in_array('downloads', self::newsHidden(), true)) {
                    $children[] = $link(__('Downloads'), route('downloads'), $request->routeIs('downloads', 'calendar', 'posts'));
                }

                foreach (self::ownLinks('news') as $own) {
                    $children[] = $link($own['label'], $own['url']);
                }

                return [
                    'url' => route('announcements.index'),
                    // Any announcement is news, except the list of procurement notices, which has its own place.
                    'active' => ($request->routeIs('announcements.*') && $type !== self::PROCUREMENT) || $request->routeIs('downloads'),
                    'children' => $children,
                ];

            case 'procurement':
                return AnnouncementType::where('slug', self::PROCUREMENT)->exists()
                    ? ['url' => route('announcements.index', ['type' => self::PROCUREMENT]), 'active' => $type === self::PROCUREMENT, 'children' => []]
                    : null;

            case 'home': return $simple('home', 'home');
            case 'schedule': return $simple('schedule', 'schedule');
            case 'departments': return $simple('departments.index', 'departments.*');
            case 'downloads': return $simple('downloads', 'downloads');
            case 'contact': return $simple('contact', 'contact');
            case 'staff': return $simple('staff', 'staff');
            case 'menu': return $simple('menu', 'menu');
            default: return null;
        }
    }

    /** Where a link of the administrator's own can be put => what the settings page calls it. */
    public const OWN_PLACES = ['top' => 'In the top bar', 'about' => 'Under About us', 'news' => 'Under News'];

    /** What News lists that can be left out => what the settings page calls it. */
    public const NEWS_OPTIONAL = ['downloads' => 'Downloads'];

    /** How many links of their own an administrator can add. */
    public const MAX_OWN = 12;

    /**
     * The administrator's own links for one place, each with its name in the reader's language.
     *
     * @return list<array{label: string, url: string}>
     */
    public static function ownLinks(string $place): array
    {
        $links = [];

        foreach (self::cleanLinks(SiteSettings::get('menu.links')) as $row) {
            if ($row['place'] === $place) {
                $links[] = ['label' => app()->getLocale() === 'en' && $row['label_en'] !== '' ? $row['label_en'] : $row['label'], 'url' => $row['url']];
            }
        }

        return $links;
    }

    /**
     * Links fit to keep, whatever the form sent: a name, a web address, a place the site knows.
     *
     * @return list<array{label: string, label_en: string, url: string, place: string}>
     */
    public static function cleanLinks(mixed $rows): array
    {
        $links = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $label = is_array($row) ? trim((string) ($row['label'] ?? '')) : '';
            $url = is_array($row) ? trim((string) ($row['url'] ?? '')) : '';
            $place = is_array($row) ? (string) ($row['place'] ?? 'top') : 'top';

            // Only a web address is ever put into a link.
            if ($label !== '' && preg_match('~^https?://[^\s"\'<>]+$~i', $url) && isset(self::OWN_PLACES[$place])) {
                $links[] = ['label' => $label, 'label_en' => trim((string) ($row['label_en'] ?? '')), 'url' => $url, 'place' => $place];
            }
        }

        return array_slice($links, 0, self::MAX_OWN);
    }

    /**
     * What News lists that an administrator has left out.
     *
     * @return list<string>
     */
    public static function newsHidden(): array
    {
        $hidden = SiteSettings::get('menu.news_hidden');

        return array_values(array_intersect(is_array($hidden) ? $hidden : [], array_keys(self::NEWS_OPTIONAL)));
    }

    /**
     * The places of a menu, in order: what was saved, or what it always was.
     *
     * @return list<string>
     */
    public static function keys(string $menu): array
    {
        [$setting, $default] = self::MENUS[$menu];
        $saved = $setting ? SiteSettings::get($setting) : null;

        return is_array($saved) && $saved !== [] ? self::clean($menu, $saved) : $default;
    }

    /**
     * What to save for the places the settings page sent: nothing when it is the untouched menu.
     *
     * @param  array<int|string, mixed>  $keys
     * @return list<string>|null
     */
    public static function toStore(string $menu, array $keys): ?array
    {
        $keys = self::clean($menu, $keys);

        return $keys === [] || $keys === self::MENUS[$menu][1] ? null : $keys;
    }

    /**
     * Known places only, each once, no more than the menu holds.
     *
     * @param  array<int|string, mixed>  $keys
     * @return list<string>
     */
    private static function clean(string $menu, array $keys): array
    {
        // The settings page sends rows (['key' => 'news']); what is stored is the bare keys.
        $keys = array_map(fn ($key) => is_array($key) ? ($key['key'] ?? null) : $key, $keys);
        $keys = array_filter($keys, fn ($key) => is_string($key) && isset(self::PLACES[$key]));

        return array_slice(array_values(array_unique($keys)), 0, self::max($menu));
    }
}
