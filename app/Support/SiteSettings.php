<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The settings an administrator can change from the panel without touching code
 * ("Site settings", App\Filament\Pages\SiteSettings).
 *
 * Some of them stand in for a config value. The rest of the code keeps reading
 * config('...') as before: when the site starts, apply() lays the saved values
 * over the config, so nothing else has to know where a value came from. A
 * setting nobody has changed has no row and keeps the value from the config
 * file (or its .env variable).
 *
 * To make another config value editable: add it to CONFIG here and a field for
 * it on the page.
 */
class SiteSettings
{
    private const CACHE_KEY = 'site-settings';

    /** Setting => how to read it back from storage. Each one overrides the config value of the same name. */
    public const CONFIG = [
        // The hospital's name, wherever the site names itself.
        'app.name' => 'string',
        'bchsp.security.staff_two_step' => 'bool',
        'bchsp.security.department_approval' => 'bool',
    ];

    /** Settings with no config value behind them: read with SiteSettings::get(). */
    public const CONTENT = [
        // The footer of every page, as an administrator arranged it (App\Support\Footer).
        'footer.about', 'footer.about_en', 'footer.links', 'footer.copyright', 'footer.show_visits',
        // Links of the administrator's own in the menus (App\Support\Menus).
        'menu.links', 'menu.news_hidden',
        // The urgent notice across the top of every page (components/site/alert).
        'alert.enabled', 'alert.text', 'alert.text_en', 'alert.url',
        // Phrases an administrator rewrote: a list of rows (App\Support\Wording).
        'wording',
        // The order of the home page's sections and which are hidden: a list of rows, or nothing (App\Support\HomeLayout).
        'home.layout',
        // The top bar's links and the phone dock's tabs, when an administrator arranged them: lists of places (App\Support\Menus).
        'menu.top', 'menu.tabs',
        // The site's colour: one, or one for each day of the week; and the black-and-white mourning look (App\Support\Theme).
        'theme.mode', 'theme.color', 'theme.days', 'theme.mourning',
        // Closed to visitors while something is being worked on (App\Http\Middleware\MaintenanceMode).
        'maintenance.enabled', 'maintenance.message',
        // The hospital's name for a reader in English, and its logo: a path on the public disk, or nothing.
        // The logo can be left out of the bar at the top of every page.
        // A photograph of the hospital behind the first screen of the home page: a path on the public disk, or nothing.
        // Since several can be given, "site.hero_slides" holds them (rows of image, caption, caption_en, url) and
        // "site.hero_image" is kept as the first of them, for what wants one picture of the hospital.
        'site.name_en', 'site.logo', 'site.show_logo', 'site.hero_image', 'site.hero_slides', 'site.hero_welcome',
        // What the home page says under the name, and of the hospital itself: plain text, one item to a line.
        'site.motto', 'identity.vision', 'identity.mission', 'identity.values',
        // Who runs the hospital and since when (App\Support\Organisation): the director's name, title and
        // photograph, the day it was founded, the mission group its groups of work stand under, and two figures
        // the site cannot count for itself: everyone who works there, and the doctors and dentists among them.
        'org.director_name', 'org.director_title', 'org.director_photo', 'org.founded_on', 'org.mission_group', 'org.staff_total', 'org.doctors',
        'contact.address', 'contact.phone', 'contact.emergency_phone', 'contact.email',
        // For the contact page: when the hospital is open, and the address of an embedded Google map.
        'contact.hours', 'contact.map',
        // Forms the hospital runs elsewhere (Google Forms) for complaints, suggestions and its satisfaction survey.
        'feedback.complaint', 'feedback.suggestion', 'feedback.survey',
        'social.facebook', 'social.line', 'social.youtube',
    ];

    /** The forms linked from the contact page, in order, with the name shown for each. */
    public const FEEDBACK = [
        'feedback.complaint' => 'Make a complaint', 'feedback.suggestion' => 'Make a suggestion', 'feedback.survey' => 'Satisfaction survey',
    ];

    /** Only a map embedded from Google Maps is put on the contact page: nothing else may be framed there. */
    public const MAP_PATTERN = '~^https://www\.google\.com/maps/embed\?[^\s"\'<>]+$~';

    /** The networks linked from the footer, in order, with the name shown for each. */
    public const SOCIAL = [
        'social.facebook' => 'Facebook', 'social.line' => 'LINE', 'social.youtube' => 'YouTube',
    ];

    /**
     * Everything that has been saved, as [key => value].
     *
     * Quietly empty when the table cannot be read (before the first migration, or with
     * the database down): the site then simply runs on its config files.
     *
     * @return array<string, mixed>
     */
    public static function saved(): array
    {
        try {
            return Cache::memo()->rememberForever(self::CACHE_KEY, fn () => DB::table('settings')->pluck('value', 'key')
                ->map(fn (?string $value) => $value === null ? null : json_decode($value, true))
                ->all());
        } catch (Throwable) {
            return [];
        }
    }

    /** Lay the saved values over the config. Called once as the site starts (AppServiceProvider). */
    public static function apply(): void
    {
        foreach (self::saved() as $key => $value) {
            if (isset(self::CONFIG[$key]) && $value !== null && $value !== '') {
                config([$key => match (self::CONFIG[$key]) {
                    'bool' => (bool) $value,
                    'int' => (int) $value,
                    default => (string) $value,
                }]);
            }
        }
    }

    /** A content setting ("social.facebook"), or a config-backed one as it stands right now. */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::CONFIG[$key])) {
            return config($key, $default);
        }

        $value = self::saved()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    /** The hospital's name in the reader's language: the English one for a reader in English, when there is one. */
    public static function name(): string
    {
        $english = app()->getLocale() === 'en' ? self::get('site.name_en') : null;

        return is_string($english) ? $english : (string) config('app.name');
    }

    /** Whether the logo is shown in the bar at the top of every page. On unless switched off. */
    public static function showsLogo(): bool
    {
        return self::get('site.show_logo', true) !== false;
    }

    /** The address of the logo an administrator uploaded, or null when there is none. */
    public static function logoUrl(): ?string
    {
        $path = self::get('site.logo');

        return is_string($path) ? Storage::disk('public')->url($path) : null;
    }

    /** How many photographs the first screen of the home page can turn through. */
    public const MAX_HERO_SLIDES = 8;

    /**
     * What is shown over a photograph of the first screen: the welcome and today's clinics, as on the site's
     * own drawing; only the photograph's own line; or nothing, so the picture is seen whole.
     */
    public const HERO_SHOWS = ['welcome', 'caption', 'none'];

    /**
     * Whether the first screen begins with the welcome on the site's own drawing, before any photograph.
     * It does until an administrator switches it off; and with no photograph at all it is all there is.
     */
    public static function heroBeginsWithWelcome(): bool
    {
        return (bool) self::get('site.hero_welcome', true) || self::heroRows() === [];
    }

    /**
     * The photographs behind the first screen of the home page, as saved: each a path on the public disk, with
     * the line that says what it shows (in both languages) and where it leads, when it has them. Only a page of
     * this site or a web address is ever kept as a link. A site set up before there could be several has its
     * one photograph as the only row.
     *
     * @return list<array{image: string, caption: string, caption_en: string, url: string, show: string}>
     */
    public static function heroRows(mixed $rows = null): array
    {
        $rows ??= self::get('site.hero_slides');
        $clean = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            // A file field hands its one file over as a list.
            $image = is_array($row) ? ($row['image'] ?? null) : null;
            $image = is_array($image) ? (array_values($image)[0] ?? null) : $image;
            $url = is_array($row) ? trim((string) ($row['url'] ?? '')) : '';

            if (is_string($image) && $image !== '') {
                $clean[] = [
                    'image' => $image,
                    'caption' => trim((string) ($row['caption'] ?? '')),
                    'caption_en' => trim((string) ($row['caption_en'] ?? '')),
                    // One kept before there was a choice had the welcome over it.
                    'show' => in_array($row['show'] ?? null, self::HERO_SHOWS, true) ? $row['show'] : 'welcome',
                    'url' => preg_match('~^(/(?!/)[^\s"\'<>\\\\]*|https?://[^\s"\'<>]+)$~i', $url) ? $url : '',
                ];
            }
        }

        if ($clean === [] && func_num_args() === 0 && is_string($one = self::get('site.hero_image')) && $one !== '') {
            $clean[] = ['image' => $one, 'caption' => '', 'caption_en' => '', 'url' => '', 'show' => 'welcome'];
        }

        return array_slice($clean, 0, self::MAX_HERO_SLIDES);
    }

    /**
     * What the first screen turns through, ready for a page: first the site's own drawing with the welcome
     * (kind "art"; see heroBeginsWithWelcome), then each photograph (kind "photo") with its address, its line
     * in the reader's language, its link, and what is shown over it.
     *
     * @return list<array{kind: string, url: ?string, caption: ?string, link: ?string, show: string}>
     */
    public static function heroSlides(): array
    {
        $photos = array_map(fn (array $row) => [
            'kind' => 'photo',
            'url' => Storage::disk('public')->url($row['image']),
            'caption' => (app()->getLocale() === 'en' && $row['caption_en'] !== '' ? $row['caption_en'] : $row['caption']) ?: null,
            'link' => $row['url'] ?: null,
            'show' => $row['show'],
        ], self::heroRows());

        return self::heroBeginsWithWelcome()
            ? [['kind' => 'art', 'url' => null, 'caption' => null, 'link' => null, 'show' => 'welcome'], ...$photos]
            : $photos;
    }

    /** The address of the first photograph of the hospital, or null when there is none. */
    public static function heroUrl(): ?string
    {
        $first = self::heroRows()[0] ?? null;

        return $first ? Storage::disk('public')->url($first['image']) : null;
    }

    /** The address of the map to embed on the contact page, or null when there is none or it is not a Google Maps embed. */
    public static function mapEmbedUrl(): ?string
    {
        $url = self::get('contact.map');

        return is_string($url) && preg_match(self::MAP_PATTERN, $url) ? $url : null;
    }

    /**
     * The contact page's forms that have an address: [name => url].
     *
     * @return array<string, string>
     */
    public static function feedbackLinks(): array
    {
        $links = [];

        foreach (self::FEEDBACK as $key => $name) {
            $url = self::get($key);

            if (is_string($url) && str_starts_with($url, 'https://')) {
                $links[$name] = $url;
            }
        }

        return $links;
    }

    /**
     * The footer's social links that have an address: [name => url].
     *
     * @return array<string, string>
     */
    public static function socialLinks(): array
    {
        $links = [];

        foreach (self::SOCIAL as $key => $name) {
            if ($url = self::get($key)) {
                $links[$name] = $url;
            }
        }

        return $links;
    }

    /**
     * Save what the settings page sent. Keys that are not settings are ignored.
     *
     * @param  array<string, mixed>  $values
     */
    public static function save(array $values): void
    {
        $now = now();

        foreach ($values as $key => $value) {
            if (! isset(self::CONFIG[$key]) && ! in_array($key, self::CONTENT, true)) {
                continue;
            }

            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'updated_at' => $now, 'created_at' => $now]);
        }

        Cache::memo()->forget(self::CACHE_KEY);
        Wording::refresh();
        self::apply();
    }
}
