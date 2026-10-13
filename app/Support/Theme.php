<?php

namespace App\Support;

/**
 * The site's colour, as an administrator set it (System > Site settings > "Colour of the site"): the one
 * accent colour that buttons, links, highlights and the admin panel all wear.
 *
 * One colour, which is what the hospital is known by. The colour of the day (seven colours, one for each
 * day of the week, changing by themselves at midnight in Thailand) is worn in one of two ways: as a touch
 * (a fine line under the top bar, the name of the day over today's clinics, a dot at the foot of the page),
 * or by the whole site in place of its one colour. And a mourning look, which turns the whole site to black
 * and white whatever the colour is.
 *
 * Until an administrator changes something the site is its own green with a touch of the day's colour:
 * a site that changes colour every day is not known by any, and the red of a Sunday reads as an alarm.
 */
class Theme
{
    /** "touch" (one colour, with a touch of the day's), "fixed" (one colour and nothing else) or "daily" (the whole site in the colour of the day). */
    public const MODE = 'theme.mode';

    public const MODES = ['touch', 'fixed', 'daily'];

    /** The one colour, "#rrggbb". */
    public const COLOR = 'theme.color';

    /** The seven colours of the days, Sunday first. */
    public const DAYS = 'theme.days';

    /** Whether the site is in black and white. */
    public const MOURNING = 'theme.mourning';

    /** The site's own green (resources/css/app.css is written in it). */
    public const GREEN = '#39ff14';

    /** The hospital's own colours, offered first: the green family, through yellow-green to yellow. */
    public const FAVOURITES = [
        '#39ff14' => 'Bright green',
        '#a3e635' => 'Yellow-green',
        '#86efac' => 'Light green',
        '#22c55e' => 'Green',
        '#15803d' => 'Dark green',
        '#facc15' => 'Yellow',
    ];

    /** The colours of the days as they are known in Thailand, Sunday first. Each can be changed. */
    public const DAY_COLOURS = ['#ef4444', '#facc15', '#ff3d71', '#22c55e', '#ff7a1a', '#38bdf8', '#a855f7'];

    public const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /** The page behind everything in each look (resources/css/app.css). */
    private const DARK_PAGE = '#0d0d0d';

    private const LIGHT_PAGE = '#ffffff';

    /** The accent is also the colour of links, so in each look it is moved away from the page until it reads as text. */
    private const READS_FROM = 4.5;

    public static function mode(): string
    {
        $saved = SiteSettings::get(self::MODE);

        return in_array($saved, self::MODES, true) ? $saved : 'touch';
    }

    public static function isDaily(): bool
    {
        return self::mode() === 'daily';
    }

    /** Today's colour, where it is worn as a touch beside the site's one colour; null where it is not. */
    public static function touchOfTheDay(): ?string
    {
        return self::mode() === 'touch' ? self::days()[now()->dayOfWeek] : null;
    }

    public static function isMourning(): bool
    {
        return (bool) SiteSettings::get(self::MOURNING, false);
    }

    /**
     * The seven colours of the days, Sunday first: the saved ones, each falling back to its own default.
     *
     * @return list<string>
     */
    public static function days(): array
    {
        $saved = SiteSettings::get(self::DAYS);

        return array_map(
            fn (int $day) => self::hex(is_array($saved) ? ($saved[$day] ?? null) : null) ?? self::DAY_COLOURS[$day],
            array_keys(self::DAY_COLOURS),
        );
    }

    /** The colour an administrator set: today's, or the one. */
    public static function accent(): string
    {
        if (self::isDaily()) {
            return self::days()[now()->dayOfWeek];
        }

        return self::hex(SiteSettings::get(self::COLOR)) ?? self::GREEN;
    }

    /**
     * The colours the pages are drawn with, worked out from one accent: for the dark look and the
     * light one, the accent, its partner for hovers, and the colour of words that sit on it.
     *
     * @return array{dark: array{brand: string, soft: string, on: string}, light: array{brand: string, soft: string, on: string}}
     */
    public static function palette(string $accent): array
    {
        // On near-black a dark accent goes lighter until it can be read; on white a pale one goes darker.
        $onDark = self::movedUntilItReads($accent, self::DARK_PAGE, '#ffffff');
        $onLight = self::movedUntilItReads($accent, self::LIGHT_PAGE, '#000000');

        return [
            'dark' => ['brand' => $onDark, 'soft' => self::mix($onDark, '#000000', 0.18), 'on' => self::wordsOn($onDark)],
            'light' => ['brand' => $onLight, 'soft' => self::mix($onLight, '#000000', 0.18), 'on' => self::wordsOn($onLight)],
        ];
    }

    /**
     * The style that re-points the site's colours at the accent, for <head> after the site's own
     * styles; nothing when the accent is the green the styles are written in.
     */
    public static function css(?string $accent = null): string
    {
        $accent = self::hex($accent) ?? self::accent();

        if ($accent === self::GREEN) {
            return '';
        }

        $palette = self::palette($accent);
        $rule = fn (array $colours) => "--color-brand:{$colours['brand']};--color-brand-soft:{$colours['soft']};--color-on-brand:{$colours['on']}";

        return ':root{'.$rule($palette['dark'])."}:root[data-theme='light']{".$rule($palette['light']).'}';
    }

    /** White or black, whichever reads better on the colour (black on a yellow or a bright green). */
    public static function wordsOn(string $colour): string
    {
        return self::contrast($colour, '#ffffff') >= self::contrast($colour, '#000000') ? '#ffffff' : '#000000';
    }

    /** "#rrggbb" in lower case, or null for anything else. */
    public static function hex(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : null;
    }

    /** How far apart two colours are to the eye, from 1 (the same) to 21 (black on white), as WCAG measures it. */
    public static function contrast(string $one, string $other): float
    {
        $lighter = max(self::luminance($one), self::luminance($other));
        $darker = min(self::luminance($one), self::luminance($other));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private static function movedUntilItReads(string $colour, string $page, string $towards): string
    {
        for ($step = 0; $step < 20 && self::contrast($colour, $page) < self::READS_FROM; $step++) {
            $colour = self::mix($colour, $towards, 0.08);
        }

        return $colour;
    }

    private static function luminance(string $hex): float
    {
        $channels = array_map(function (string $pair) {
            $value = hexdec($pair) / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /** A colour moved towards another by a share between 0 and 1. */
    private static function mix(string $colour, string $towards, float $share): string
    {
        $from = array_map('hexdec', str_split(ltrim($colour, '#'), 2));
        $to = array_map('hexdec', str_split(ltrim($towards, '#'), 2));

        return '#'.implode('', array_map(
            fn (int $a, int $b) => str_pad(dechex((int) round($a + ($b - $a) * $share)), 2, '0', STR_PAD_LEFT),
            $from, $to,
        ));
    }
}
