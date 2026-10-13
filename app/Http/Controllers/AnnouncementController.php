<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * The announcements as visitors read them: the list, which is also the archive,
 * narrowed by type, by when it came out (a fiscal year, today, this week, this month, or dates of the reader's own)
 * by the department that put it out and by a word in the title, as rows or as cards; and one announcement with its files.
 */
class AnnouncementController extends Controller
{
    private const PER_PAGE = 15;

    /** The ways the list can be looked at: rows, or cards with each announcement's cover. */
    public const VIEWS = ['list', 'cards'];

    /** What "when" can be besides a fiscal year => what the list calls it. */
    public const PERIODS = ['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'custom' => 'Choose the dates'];

    public function index(Request $request): View
    {
        $type = is_string($request->query('type'))
            ? AnnouncementType::where('slug', $request->query('type'))->first()
            : null;

        $years = Announcement::fiscalYears($type?->id);
        $year = is_numeric($request->query('year')) && isset($years[(int) $request->query('year')]) ? (int) $request->query('year') : null;
        // The same choice also takes a period that is not a fiscal year (?year=week), or two dates (?year=custom&from=...&to=...).
        $period = is_string($request->query('year')) && isset(self::PERIODS[$request->query('year')]) ? $request->query('year') : null;
        [$from, $until] = match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom' => self::dates($request->query('from'), $request->query('to')),
            default => [null, null],
        };
        $term = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 80) : '';
        // The departments that have put something out: only those are offered, and only one of those is taken.
        $departments = Department::shown()->whereHas('announcements', fn ($query) => $query->published())->get();
        $department = is_string($request->query('department')) ? $departments->firstWhere('slug', $request->query('department')) : null;

        $opening = ! $type && ! $department && ! $year && ! $period && $term === '' && (int) $request->query('page', 1) <= 1;
        // The figures in the band at the top: how much there is, and how much of it is new.
        $stats = [
            'all' => Announcement::published()->count(),
            'week' => Announcement::published()->where('starts_at', '>=', now()->startOfWeek())->count(),
            'month' => Announcement::published()->where('starts_at', '>=', now()->startOfMonth())->count(),
        ];

        return view('announcements.index', [
            'type' => $type,
            'department' => $department,
            'departments' => $departments,
            // What is shown only on the whole list as it opens: narrowed, or further on, the reader is after something already.
            'mostRead' => $opening ? Announcement::mostRead() : collect(),
            // The three newest, set large; not while there are so few that the list below would only say them again.
            'featured' => $opening && $stats['all'] >= 4 ? Announcement::current()->with('type', 'department')->inListOrder()->limit(3)->get() : collect(),
            // What has been given a day that is still to come (the calendar has the rest).
            'coming' => $opening ? Announcement::published()->whereDate('event_on', '>=', now()->toDateString())->orderBy('event_on')->orderBy('id')->limit(4)->get() : collect(),
            // The departments that posted most lately, newest first.
            'posting' => $opening
                ? Department::shown()->whereKey($lately = Announcement::current()->whereNotNull('department_id')->latest('starts_at')->limit(60)->pluck('department_id')->unique()->take(6)->values())->get()->sortBy(fn (Department $one) => $lately->search($one->id))->values()
                : collect(),
            'stats' => $stats,
            // How many each kind holds, for its tile.
            'counts' => Announcement::published()->selectRaw('announcement_type_id, count(*) as held')->groupBy('announcement_type_id')->pluck('held', 'announcement_type_id'),
            'types' => AnnouncementType::orderBy('sort_order')->orderBy('name')->get(),
            'year' => $year,
            'years' => $years,
            'period' => $period,
            'from' => $period === 'custom' ? $from?->toDateString() : null,
            'to' => $period === 'custom' ? $until?->toDateString() : null,
            'term' => $term,
            'look' => in_array($request->query('view'), self::VIEWS, true) ? $request->query('view') : self::VIEWS[0],
            'announcements' => Announcement::published()
                ->with('type', 'department')
                ->when($type, fn ($query) => $query->where('announcement_type_id', $type->id))
                ->when($department, fn ($query) => $query->where('department_id', $department->id))
                ->when($year, fn ($query) => $query->ofFiscalYear($year))
                ->when($from, fn ($query) => $query->where('starts_at', '>=', $from))
                ->when($until, fn ($query) => $query->where('starts_at', '<=', $until))
                // The characters that mean "anything" to LIKE are looked for as themselves (see SearchController).
                ->when(mb_strlen($term) >= 2, fn ($query) => $query->where(fn ($query) => $query
                    ->whereRaw("title like ? escape '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%'])
                    ->orWhereRaw("title_en like ? escape '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%'])))
                ->inListOrder()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function show(Announcement $announcement): View
    {
        // Staff who are signed in can read one before its time, to check it.
        abort_unless($announcement->isPublished() || auth()->check(), 404);

        return view('announcements.show', [
            'announcement' => $announcement->load('type', 'department'),
            // Somewhere to go next: the newest others of the same kind that are showing now.
            'more' => Announcement::current()->with('type')->whereKeyNot($announcement->id)
                ->when($announcement->announcement_type_id, fn ($query, $type) => $query->where('announcement_type_id', $type))
                ->orderByDesc('starts_at')->orderByDesc('id')->limit(4)->get(),
        ]);
    }

    /** /calendar: what happens in a month, day by day. ?month=2026-11; anything else is this month. */
    public function calendar(Request $request): View
    {
        $asked = $request->query('month');
        $month = is_string($asked) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $asked) ? Carbon::createFromFormat('Y-m-d', $asked.'-01')->startOfDay() : now()->startOfMonth();

        return view('calendar', ['month' => $month, 'days' => Announcement::calendarOf($month)]);
    }

    /**
     * The two dates a reader typed, as the first moment of one and the last of the other. Either may be left out;
     * typed the wrong way round they are swapped; anything that is not a date is nothing.
     *
     * @return array{?Carbon, ?Carbon}
     */
    private static function dates(mixed $from, mixed $to): array
    {
        $date = fn (mixed $value) => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && Carbon::canBeCreatedFromFormat($value, 'Y-m-d')
            ? Carbon::createFromFormat('Y-m-d', $value)
            : null;

        [$from, $to] = [$date($from), $date($to)];

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from?->startOfDay(), $to?->endOfDay()];
    }

    /** /blog and /blog/{slug}: where articles were, before they became announcements. To the same one when it is there, or to the list. */
    public function fromBlog(?string $slug = null): RedirectResponse
    {
        $announcement = $slug === null ? null : Announcement::published()->where('slug', $slug)->first();

        return redirect($announcement ? route('announcements.show', $announcement) : route('announcements.index'), 301);
    }
}
