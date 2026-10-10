<?php

namespace App\Support;

use App\Models\AnnouncementType;
use App\Models\ItaItem;
use App\Models\SitePage;
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
 * "About us" opens onto the pages an administrator filed there (System > Pages),
 * the departments and the contact page. "ITA" opens onto its fiscal years and
 * the pages filed there. "News" opens onto the kinds of
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
        'news' => ['News', 'News', 'megaphone'],
        'procurement' => ['Procurement', 'Procurement', 'clipboard-document-list'],
        'blog' => ['Articles', 'Articles', 'newspaper'],
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
     * @return list<array{key: string, label: string, short: string, icon: string, url: string, active: bool, children: list<array{label: string, url: string, active: bool}>}>
     */
    public static function items(string $menu): array
    {
        $items = [];

        foreach (self::keys($menu) as $key) {
            if ($place = self::place($key)) {
                [$label, $short, $icon] = self::PLACES[$key];

                $items[] = compact('key', 'label', 'short', 'icon') + $place;
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
    private static function place(string $key): ?array
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
                $children[] = $link(__('Departments'), route('departments.index'), $request->routeIs('departments.*'));
                $children[] = $link(__('Contact us'), route('contact'), $request->routeIs('contact'));

                return ['url' => $children[0]['url'], 'active' => in_array(true, array_column($children, 'active'), true), 'children' => $children];

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
                    // One year and nothing else is just the page itself: no list to open.
                    'children' => count($children) > 1 ? $children : [],
                ];

            case 'news':
                $children = AnnouncementType::where('slug', '!=', self::PROCUREMENT)->orderBy('sort_order')->orderBy('name')->get()
                    ->map(fn (AnnouncementType $one) => $link($one->localName(), route('announcements.index', ['type' => $one->slug]), $type === $one->slug))
                    ->all();
                $children[] = $link(__('Health articles'), route('blog.index'), $request->routeIs('blog.*'));
                $children[] = $link(__('Downloads'), route('downloads'), $request->routeIs('downloads'));

                return [
                    'url' => route('announcements.index'),
                    // Any announcement is news, except the list of procurement notices, which has its own place.
                    'active' => ($request->routeIs('announcements.*') && $type !== self::PROCUREMENT) || $request->routeIs('blog.*', 'downloads'),
                    'children' => $children,
                ];

            case 'procurement':
                return AnnouncementType::where('slug', self::PROCUREMENT)->exists()
                    ? ['url' => route('announcements.index', ['type' => self::PROCUREMENT]), 'active' => $type === self::PROCUREMENT, 'children' => []]
                    : null;

            case 'home': return $simple('home', 'home');
            case 'blog': return $simple('blog.index', 'blog.*');
            case 'departments': return $simple('departments.index', 'departments.*');
            case 'downloads': return $simple('downloads', 'downloads');
            case 'contact': return $simple('contact', 'contact');
            case 'staff': return $simple('staff', 'staff');
            case 'menu': return $simple('menu', 'menu');
            default: return null;
        }
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
