<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * The site's two main menus, as an administrator arranged them (System > Site
 * settings > "Menus"): the links in the top bar of a wide screen, and the tabs
 * of the dock at the bottom of a phone or tablet.
 *
 * Each is an ordered pick from PLACES. Until an administrator changes one,
 * nothing is saved and the menu is what it always was (the lists in MENUS below).
 *
 * To add a place: give it a line in PLACES, and put it in a default list if it
 * should be there from the start.
 */
class Menus
{
    /**
     * Place => [route name, label, short label for a tab, heroicon, pattern of routes that mark it current].
     */
    public const PLACES = [
        'home' => ['home', 'Home', 'Home', 'home', 'home'],
        'blog' => ['blog.index', 'Articles', 'Articles', 'newspaper', 'blog.*'],
    ];

    /** Menu => [setting, what it holds when untouched, how many it can hold]. */
    private const MENUS = [
        'top' => ['menu.top', ['home', 'blog'], 7],
        // Five: more than that and a thumb misses.
        'tabs' => ['menu.tabs', ['home', 'blog'], 5],
    ];

    public static function max(string $menu): int
    {
        return self::MENUS[$menu][2];
    }

    /**
     * The menu as it should be drawn.
     *
     * @return list<array{key: string, route: string, label: string, short: string, icon: string, pattern: string}>
     */
    public static function items(string $menu): array
    {
        $items = [];

        foreach (self::keys($menu) as $key) {
            [$route, $label, $short, $icon, $pattern] = self::PLACES[$key];

            // A place whose page has been taken out of the site is left out rather than breaking every page.
            if (Route::has($route)) {
                $items[] = compact('key', 'route', 'label', 'short', 'icon', 'pattern');
            }
        }

        return $items;
    }

    /**
     * The places of a menu, in order: what was saved, or what it always was.
     *
     * @return list<string>
     */
    public static function keys(string $menu): array
    {
        [$setting, $default] = self::MENUS[$menu];
        $saved = SiteSettings::get($setting);

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
        // The settings page sends rows (['key' => 'blog']); what is stored is the bare keys.
        $keys = array_map(fn ($key) => is_array($key) ? ($key['key'] ?? null) : $key, $keys);
        $keys = array_filter($keys, fn ($key) => is_string($key) && isset(self::PLACES[$key]));

        return array_slice(array_values(array_unique($keys)), 0, self::max($menu));
    }
}
