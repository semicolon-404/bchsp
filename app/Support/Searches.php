<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * What readers search the site for (table search_terms): the words and how much was found, counted by day.
 * Nothing about the reader is kept. The report (App\Support\Reports) shows what is searched most, and what is
 * searched and not found: that is what the hospital should write next.
 */
class Searches
{
    /** Kept this long, then dropped (routes/console.php). */
    public const KEEP_DAYS = 400;

    private const NOT_PEOPLE = '/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless/i';

    /** One search by a person; the same words again in the same visit are not counted twice. */
    public static function record(Request $request, string $term, int $found): void
    {
        $term = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $term)));

        if ($term === '' || preg_match(self::NOT_PEOPLE, (string) $request->userAgent()) || ! $request->hasSession()) {
            return;
        }

        $searched = (array) $request->session()->get('searched', []);

        if (in_array($term, $searched, true)) {
            return;
        }

        $request->session()->put('searched', array_slice([...$searched, $term], -30));

        $key = ['day' => today()->toDateString(), 'term' => $term];

        if (DB::table('search_terms')->where($key)->update(['searches' => DB::raw('searches + 1'), 'found' => $found]) === 0) {
            DB::table('search_terms')->insertOrIgnore($key + ['searches' => 1, 'found' => $found]);
        }
    }

    /**
     * The words searched most in a period, and those that found nothing.
     *
     * @return array{top: list<array{term: string, searches: int}>, empty: list<array{term: string, searches: int}>}
     */
    public static function between(Carbon $from, Carbon $until, int $limit = 15): array
    {
        $rows = fn (bool $empty) => DB::table('search_terms')
            ->whereBetween('day', [$from->toDateString(), $until->toDateString()])
            ->selectRaw('term, sum(searches) as searches, min(found) as least')
            ->groupBy('term')
            ->when($empty, fn ($query) => $query->havingRaw('min(found) = 0'))
            ->orderByDesc('searches')->orderBy('term')->limit($limit)->get()
            ->map(fn ($row) => ['term' => (string) $row->term, 'searches' => (int) $row->searches])->all();

        return ['top' => $rows(false), 'empty' => $rows(true)];
    }

    public static function prune(): int
    {
        return DB::table('search_terms')->where('day', '<', today()->subDays(self::KEEP_DAYS)->toDateString())->delete();
    }
}
