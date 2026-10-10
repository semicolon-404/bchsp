<?php

namespace App\Support;

/**
 * The order of the home page's sections, and which of them are shown, as an
 * administrator arranged them (System > Site settings > "Home page layout").
 *
 * Until an administrator changes something nothing is saved, and the page
 * keeps the order it always had (SECTIONS below), without the ones that are off at first. A section with nothing in it
 * (no slide, no article yet) takes no room, shown or not.
 */
class HomeLayout
{
    private const SETTING = 'home.layout';

    /** Section => the name the settings page gives it. In the order the page has until someone arranges it. */
    public const SECTIONS = [
        'slides' => 'Slides',
        'hero' => 'Name of the hospital and phone buttons',
        'shortcuts' => 'Shortcuts to what visitors come for',
        'announcements' => 'Announcements',
        'posters' => 'Posters',
        'departments' => 'Departments',
        'articles' => 'Articles',
    ];

    /**
     * Off until an administrator switches them on. The list of departments is reference material, not
     * what the home page is for: it has a page of its own and a shortcut to it.
     */
    public const OFF_AT_FIRST = ['departments'];

    /** Cannot be hidden: it holds the page's heading and the buttons to call the hospital. */
    public const LOCKED = ['hero'];

    /**
     * The sections that are shown, in order: what resources/views/home.blade.php draws.
     *
     * @return list<string>
     */
    public static function shown(): array
    {
        return array_column(array_filter(self::rows(), fn (array $row) => $row['shown']), 'key');
    }

    /**
     * Every section with whether it is shown, in the saved order: what the settings page lists.
     *
     * @return list<array{key: string, shown: bool}>
     */
    public static function rows(): array
    {
        $saved = SiteSettings::get(self::SETTING);

        return self::clean(is_array($saved) ? $saved : []);
    }

    /**
     * What to save for the rows the settings page sent: null when they are the untouched
     * default, so that a section added to the site later takes its natural place.
     *
     * @param  array<int|string, mixed>  $rows
     * @return list<array{key: string, shown: bool}>|null
     */
    public static function toStore(array $rows): ?array
    {
        $rows = self::clean($rows);

        return $rows === self::clean([]) ? null : $rows;
    }

    /**
     * Rows as they should be whatever was handed in: known sections only, each once,
     * the ones missing added at the end, the locked ones shown.
     *
     * @param  array<int|string, mixed>  $rows
     * @return list<array{key: string, shown: bool}>
     */
    private static function clean(array $rows): array
    {
        $clean = [];

        foreach ($rows as $row) {
            $key = is_array($row) ? ($row['key'] ?? null) : null;

            if (is_string($key) && isset(self::SECTIONS[$key]) && ! isset($clean[$key])) {
                $clean[$key] = ['key' => $key, 'shown' => in_array($key, self::LOCKED, true) || (bool) ($row['shown'] ?? true)];
            }
        }

        foreach (array_keys(self::SECTIONS) as $key) {
            $clean[$key] ??= ['key' => $key, 'shown' => ! in_array($key, self::OFF_AT_FIRST, true)];
        }

        return array_values($clean);
    }
}
