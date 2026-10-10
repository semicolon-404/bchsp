<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * How many pages of the site were opened, day by day: the figure in the footer,
 * and what an assessment asks for ("statistics of use").
 *
 * It counts pages opened, not people: telling people apart would mean keeping
 * something about each of them (an address, a cookie), and the site keeps
 * nothing about its visitors. One number a day is all that is stored.
 */
class Visits
{
    private const TOTALS = 'visits:totals';

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
            $today = now()->toDateString();

            // Two statements, not an "upsert": the same on MariaDB and on the tests' SQLite, and a lost race costs one count.
            if (DB::table('site_visits')->where('day', $today)->increment('views') === 0) {
                DB::table('site_visits')->insertOrIgnore(['day' => $today, 'views' => 1]);
            }
        } catch (Throwable $e) {
            // Counting must never stop a page from opening.
            report($e);
        }
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
                    'today' => (int) DB::table('site_visits')->where('day', now()->toDateString())->value('views'),
                    'month' => (int) DB::table('site_visits')->where('day', '>=', now()->startOfMonth()->toDateString())->sum('views'),
                    'all' => (int) DB::table('site_visits')->sum('views'),
                ];
            } catch (Throwable) {
                return ['today' => 0, 'month' => 0, 'all' => 0];
            }
        });
    }
}
