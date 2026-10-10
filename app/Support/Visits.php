<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * How many pages of the site were opened, day by day and part by part: the
 * figure in the footer, the panel's dashboard, and the reports for executives.
 *
 * It counts pages opened, not people: telling people apart would mean keeping
 * something about each of them (an address, a cookie), and the site keeps
 * nothing about its visitors. What is stored is a number for each day and each
 * part of the site, and a number on each announcement and article.
 */
class Visits
{
    private const TOTALS = 'visits:totals';

    /** Part of the site => what the panel calls it. In the order reports list them. */
    public const SECTIONS = [
        'home' => 'Home page',
        'news' => 'News and announcements',
        'procurement' => 'Procurement',
        'ita' => 'ITA',
        'articles' => 'Articles',
        'schedule' => 'Clinic schedule',
        'departments' => 'Departments',
        'pages' => 'About us and other pages',
        'contact' => 'Contact',
        'downloads' => 'Downloads',
        'search' => 'Search',
        'other' => 'Other',
    ];

    /** Programs that read the site for a search engine or a preview are not visitors. */
    private const NOT_PEOPLE = '/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless/i';

    /** Count this page for today, when it is a person opening a page of the public site. */
    public static function count(Request $request): void
    {
        $agent = (string) $request->userAgent();

        if ($agent === '' || preg_match(self::NOT_PEOPLE, $agent) || $request->user() !== null) {
            return;
        }

        try {
            $key = ['day' => now()->toDateString(), 'section' => self::sectionOf($request)];

            // Two statements, not an "upsert": the same on MariaDB and on the tests' SQLite, and a lost race costs one count.
            if (DB::table('site_visits')->where($key)->increment('views') === 0) {
                DB::table('site_visits')->insertOrIgnore($key + ['views' => 1]);
            }

            // The announcement or article itself, so that the most read can be named. Without its "last changed" moving.
            $item = $request->route('announcement') ?? $request->route('post');

            if ($item instanceof Announcement || $item instanceof BlogPost) {
                DB::table($item->getTable())->where('id', $item->getKey())->increment('views');
            }
        } catch (Throwable $e) {
            // Counting must never stop a page from opening.
            report($e);
        }
    }

    /** Which part of the site a page belongs to. */
    public static function sectionOf(Request $request): string
    {
        $route = (string) $request->route()?->getName();

        if (str_starts_with($route, 'announcements.')) {
            $type = $request->route('announcement')?->type?->slug ?? $request->query('type');

            return $type === Menus::PROCUREMENT ? 'procurement' : 'news';
        }

        return match (true) {
            $route === 'home' => 'home',
            $route === 'ita' => 'ita',
            str_starts_with($route, 'blog.') => 'articles',
            $route === 'schedule' => 'schedule',
            str_starts_with($route, 'departments.') => 'departments',
            in_array($route, ['pages.show', 'about'], true) => 'pages',
            $route === 'contact' => 'contact',
            $route === 'downloads' => 'downloads',
            $route === 'search' => 'search',
            default => 'other',
        };
    }

    /**
     * Pages opened today, this month and in all. Kept for a minute: the footer of every page shows it.
     *
     * @return array{today: int, month: int, all: int}
     */
    public static function totals(): array
    {
        return Cache::remember(self::TOTALS, 60, function () {
            try {
                return [
                    'today' => (int) DB::table('site_visits')->where('day', now()->toDateString())->sum('views'),
                    'month' => (int) DB::table('site_visits')->where('day', '>=', now()->startOfMonth()->toDateString())->sum('views'),
                    'all' => (int) DB::table('site_visits')->sum('views'),
                ];
            } catch (Throwable) {
                return ['today' => 0, 'month' => 0, 'all' => 0];
            }
        });
    }

    /** Pages opened between two days, both included. */
    public static function between(Carbon $from, Carbon $until): int
    {
        return (int) DB::table('site_visits')->whereBetween('day', [$from->toDateString(), $until->toDateString()])->sum('views');
    }

    /**
     * Pages opened on each day between two days, days with none included: ['2026-10-01' => 12, ...].
     *
     * @return array<string, int>
     */
    public static function daily(Carbon $from, Carbon $until): array
    {
        $counted = DB::table('site_visits')
            ->whereBetween('day', [$from->toDateString(), $until->toDateString()])
            ->selectRaw('day, sum(views) as views')->groupBy('day')->pluck('views', 'day');

        $days = [];

        for ($day = $from->copy()->startOfDay(); $day->lte($until); $day->addDay()) {
            $days[$day->toDateString()] = (int) ($counted[$day->toDateString()] ?? 0);
        }

        return $days;
    }

    /**
     * Pages opened in each month between two days: ['2026-10' => 340, ...], months with none included.
     *
     * @return array<string, int>
     */
    public static function monthly(Carbon $from, Carbon $until): array
    {
        $months = [];

        foreach (self::daily($from, $until) as $day => $views) {
            $months[substr($day, 0, 7)] = ($months[substr($day, 0, 7)] ?? 0) + $views;
        }

        return $months;
    }

    /**
     * Pages opened in each part of the site between two days, the most opened first; parts with none left out.
     *
     * @return array<string, int> ['news' => 120, 'home' => 90, ...]
     */
    public static function bySection(Carbon $from, Carbon $until): array
    {
        return DB::table('site_visits')
            ->whereBetween('day', [$from->toDateString(), $until->toDateString()])
            ->selectRaw('section, sum(views) as views')->groupBy('section')->orderByDesc('views')
            ->pluck('views', 'section')->map(fn ($views) => (int) $views)->all();
    }
}
