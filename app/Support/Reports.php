<?php

namespace App\Support;

use App\Models\AccessLog;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\ItaItem;
use App\Models\User;
use App\Models\WorkGroup;
use Illuminate\Support\Carbon;

/**
 * What the site and its staff did over a period, worked out for the panel's
 * dashboard and for the report an administrator prints or exports for the
 * hospital's executives (App\Filament\Pages\Reports).
 *
 * A period is two days, both included. The usual ones are a month, a quarter
 * of the fiscal year, and a fiscal year (October to September, named by its
 * Buddhist year).
 */
class Reports
{
    /** The kinds of period the report page offers => what it calls them. */
    public const PERIODS = ['month' => 'A month', 'quarter' => 'A quarter of the fiscal year', 'year' => 'A fiscal year'];

    /**
     * The first and last day of a period, from what the report page's form holds.
     *
     * @param  array{period?: string, year?: int|string, month?: int|string, quarter?: int|string}  $choice
     * @return array{0: Carbon, 1: Carbon, 2: string} the two days and the period's name ("ปีงบประมาณ 2569")
     */
    public static function period(array $choice): array
    {
        $year = (int) ($choice['year'] ?? Announcement::fiscalYearOf(now()));
        [$start] = Announcement::fiscalYearDates($year);

        switch ($choice['period'] ?? 'year') {
            case 'month':
                // A month of the fiscal year: October (10) to December belong to the calendar year before.
                $month = min(12, max(1, (int) ($choice['month'] ?? now()->month)));
                $from = Carbon::create($year - 543 - ($month >= 10 ? 1 : 0), $month, 1, 0, 0, 0, config('app.timezone'));

                return [$from, $from->copy()->endOfMonth(), $from->locale(app()->getLocale())->translatedFormat('F').' '.($from->year + 543)];

            case 'quarter':
                $quarter = min(4, max(1, (int) ($choice['quarter'] ?? 1)));
                $from = $start->copy()->addMonths(($quarter - 1) * 3);

                return [$from, $from->copy()->addMonths(3)->subDay()->endOfDay(), __('Quarter :quarter of fiscal year :year', ['quarter' => $quarter, 'year' => $year])];

            default:
                return [$start, $start->copy()->addYear()->subDay()->endOfDay(), __('Fiscal year :year', ['year' => $year])];
        }
    }

    /**
     * Everything the report shows for a period.
     *
     * @return array<string, mixed>
     */
    public static function summary(Carbon $from, Carbon $until): array
    {
        $announcements = Announcement::query()->where('status', Announcement::STATUS_PUBLISHED)->whereBetween('starts_at', [$from, $until])->where('starts_at', '<=', now());
        $types = AnnouncementType::orderBy('sort_order')->get();
        // Counted by the database in one grouped query each, not one query a department or kind (which grows with them).
        $byType = (clone $announcements)->toBase()->selectRaw('announcement_type_id, count(*) as posts')->groupBy('announcement_type_id')->pluck('posts', 'announcement_type_id');
        $byDepartment = (clone $announcements)->toBase()->whereNotNull('department_id')
            ->selectRaw('department_id, count(*) as posts, sum(views) as views, sum(helpful) as helpful')->groupBy('department_id')->get()->keyBy('department_id');

        return [
            'from' => $from,
            'until' => $until,
            'visits' => [
                'total' => Visits::between($from, $until),
                'monthly' => Visits::monthly($from, $until->copy()->min(now())),
                'sections' => Visits::bySection($from, $until),
            ],
            'announcements' => [
                'total' => (clone $announcements)->count(),
                // Every type, also those with none: an executive should see "0", not wonder whether it was left out.
                'types' => $types->mapWithKeys(fn (AnnouncementType $type) => [$type->name => (int) ($byType[$type->id] ?? 0)])->all(),
                'monthly' => self::perMonth((clone $announcements)->pluck('starts_at'), $from, $until),
                'top' => (clone $announcements)->with('type')->where('views', '>', 0)->orderByDesc('views')->limit(10)->get(),
            ],
            // The departments that posted in the period, the busiest first.
            'departments' => Department::orderBy('sort_order')->orderBy('name')->get()
                ->map(fn (Department $department) => [
                    'name' => $department->name,
                    'posts' => (int) ($byDepartment[$department->id]->posts ?? 0),
                    'views' => (int) ($byDepartment[$department->id]->views ?? 0),
                    'helpful' => (int) ($byDepartment[$department->id]->helpful ?? 0),
                ])
                ->filter(fn (array $row) => $row['posts'] > 0)->sortByDesc('posts')->values()->all(),
            // What readers typed into the search: the most often, and what found nothing.
            'searches' => Searches::between($from, $until),
            'ita' => self::ita(Announcement::fiscalYearOf($until->copy()->min(now()))),
            'staff' => self::staff($from, $until),
            'complaints' => self::complaints($from, $until),
        ];
    }

    /**
     * The matters sent in during a period, for executives: how many, how many finished and how quickly, how many
     * still open and past their days, by kind, by month and by the group of work they concern, and those to follow
     * up. Nobody's name, number or address is in it: a matter is its code.
     *
     * @return array<string, mixed>
     */
    public static function complaints(Carbon $from, Carbon $until): array
    {
        $in = Complaint::query()->whereBetween('created_at', [$from, $until]);
        $days = SiteSettings::complaintDays();
        $all = (clone $in)->get(['id', 'code', 'kind', 'title', 'status', 'work_group_id', 'created_at', 'closed_at']);
        $done = $all->filter(fn (Complaint $one) => $one->status === 'done');
        $open = $all->reject(fn (Complaint $one) => in_array($one->status, Complaint::CLOSED, true));
        $late = $open->filter(fn (Complaint $one) => $one->created_at->lt(now()->subDays($days)));
        $groups = WorkGroup::whereIn('id', $all->pluck('work_group_id')->filter()->unique())->pluck('name', 'id');

        return [
            'total' => $all->count(),
            'done' => $done->count(),
            'open' => $open->count(),
            'late' => $late->count(),
            'days' => $days,
            'average_days' => $done->filter->closed_at->isNotEmpty() ? round($done->filter->closed_at->avg(fn (Complaint $one) => $one->created_at->diffInHours($one->closed_at) / 24), 1) : null,
            'kinds' => collect(Complaint::KINDS)->keys()->mapWithKeys(fn (string $kind) => [$kind => $all->where('kind', $kind)->count()])->all(),
            'monthly' => self::perMonth($all->pluck('created_at'), $from, $until),
            'groups' => $all->whereNotNull('work_group_id')->countBy('work_group_id')->sortDesc()->take(5)->mapWithKeys(fn ($count, $id) => [$groups[$id] ?? '–' => $count])->all(),
            'follow' => $open->sortBy('created_at')->take(15)->map(fn (Complaint $one) => [
                'code' => $one->code, 'kind' => $one->kind, 'title' => $one->title, 'status' => $one->status,
                'received' => $one->created_at, 'days' => (int) $one->created_at->diffInDays(now()), 'late' => $late->contains($one),
            ])->values()->all(),
        ];
    }

    /**
     * How complete a fiscal year's ITA is: how many items there are, and how many have evidence published.
     *
     * @return array{year: int, items: int, with_evidence: int, percent: int, missing: list<string>}
     */
    public static function ita(int $year): array
    {
        $items = ItaItem::where('year', $year)->where('is_active', true)->orderBy('number')->get();
        $done = $items->filter(fn (ItaItem $item) => $item->evidence() !== []);

        return [
            'year' => $year,
            'items' => $items->count(),
            'with_evidence' => $done->count(),
            'percent' => $items->isEmpty() ? 0 : (int) round($done->count() / $items->count() * 100),
            'missing' => $items->reject(fn (ItaItem $item) => $item->evidence() !== [])->pluck('code')->all(),
        ];
    }

    /**
     * What each member of staff did in the panel over the period, from the access log: the busiest first.
     * Staff with nothing in the period are listed too, so that an idle account is seen.
     *
     * @return list<array{name: string, role: string, created: int, changed: int, deleted: int, sign_ins: int, last_sign_in: ?Carbon}>
     */
    public static function staff(Carbon $from, Carbon $until): array
    {
        $counts = AccessLog::query()->whereBetween('created_at', [$from, $until])->whereNotNull('user_id')
            ->selectRaw('user_id, event, count(*) as times')->groupBy('user_id', 'event')->get()->groupBy('user_id');
        $last = AccessLog::query()->where('event', 'login')->selectRaw('user_id, max(created_at) as at')->groupBy('user_id')->pluck('at', 'user_id');

        return User::orderBy('name')->get()->map(function (User $user) use ($counts, $last) {
            $of = fn (string $event) => (int) ($counts[$user->id] ?? collect())->firstWhere('event', $event)?->times;

            return [
                'name' => $user->name,
                'role' => $user->role->label(),
                'created' => $of('admin.created'),
                'changed' => $of('admin.updated') + $of('admin.settings'),
                'deleted' => $of('admin.deleted'),
                'sign_ins' => $of('login'),
                'last_sign_in' => isset($last[$user->id]) ? Carbon::parse($last[$user->id]) : null,
            ];
        })->sortByDesc(fn (array $row) => $row['created'] + $row['changed'] + $row['deleted'])->values()->all();
    }

    /**
     * The report as rows of a spreadsheet: a title, then each table under its heading.
     *
     * @return list<list<string|int>>
     */
    public static function rows(array $summary, string $title): array
    {
        $rows = [[config('app.name')], [__('Website report').': '.$title], [__('Made on').' '.now()->format('Y-m-d H:i')], []];

        $rows[] = [__('Pages opened')];
        $rows[] = [__('In all'), $summary['visits']['total']];
        foreach ($summary['visits']['monthly'] as $month => $views) {
            $rows[] = [$month, $views];
        }
        $rows[] = [];

        $rows[] = [__('Pages opened, by part of the site')];
        foreach ($summary['visits']['sections'] as $section => $views) {
            $rows[] = [__(Visits::SECTIONS[$section] ?? $section), $views];
        }
        $rows[] = [];

        $rows[] = [__('Announcements published'), $summary['announcements']['total']];
        foreach ($summary['announcements']['types'] as $type => $count) {
            $rows[] = [$type, $count];
        }
        foreach ($summary['announcements']['monthly'] as $month => $count) {
            $rows[] = [$month, $count];
        }
        $rows[] = [];

        $rows[] = [__('Most read'), __('Type'), __('Times opened')];
        foreach ($summary['announcements']['top'] as $one) {
            $rows[] = [$one->title, (string) $one->type?->name, (int) $one->views];
        }
        $rows[] = [];

        $rows[] = [__('Searched for most'), __('Times')];
        foreach ($summary['searches']['top'] as $row) {
            $rows[] = [$row['term'], $row['searches']];
        }
        $rows[] = [];

        $rows[] = [__('Searched for and not found'), __('Times')];
        foreach ($summary['searches']['empty'] as $row) {
            $rows[] = [$row['term'], $row['searches']];
        }
        $rows[] = [];

        $rows[] = [__('Posts of the departments'), __('Posts'), __('Times opened'), __('Found useful')];
        foreach ($summary['departments'] as $row) {
            $rows[] = [$row['name'], $row['posts'], $row['views'], $row['helpful']];
        }
        $rows[] = [];

        $rows[] = ['ITA '.$summary['ita']['year'], __(':done of :all items have documents', ['done' => $summary['ita']['with_evidence'], 'all' => $summary['ita']['items']]), $summary['ita']['percent'].'%'];
        $rows[] = [];

        $rows[] = [__('Staff'), __('Role'), __('Created'), __('Changed'), __('Deleted'), __('Sign-ins'), __('Last sign-in')];
        foreach ($summary['staff'] as $row) {
            $rows[] = [$row['name'], __($row['role']), $row['created'], $row['changed'], $row['deleted'], $row['sign_ins'], $row['last_sign_in']?->format('Y-m-d H:i') ?? '-'];
        }

        $c = $summary['complaints'];
        $rows[] = [];
        $rows[] = [__('Complaints and suggestions'), __('In all'), __('Finished'), __('Still open'), __('Past their days'), __('Average days to finish')];
        $rows[] = ['', $c['total'], $c['done'], $c['open'], $c['late'], $c['average_days'] ?? '-'];
        foreach ($c['kinds'] as $kind => $count) {
            $rows[] = [__(Complaint::KINDS[$kind]), $count];
        }
        foreach ($c['groups'] as $group => $count) {
            $rows[] = [$group, $count];
        }
        $rows[] = [__('To follow up'), __('Kind'), __('Received'), __('Days open'), __('Where it stands')];
        foreach ($c['follow'] as $one) {
            $rows[] = [$one['code'], __(Complaint::KINDS[$one['kind']] ?? $one['kind']), $one['received']->format('Y-m-d'), $one['days'], __(Complaint::STATUSES[$one['status']] ?? $one['status'])];
        }

        return $rows;
    }

    /**
     * How many of a list of dates fall in each month of a period, months with none included.
     *
     * @param  iterable<Carbon>  $dates
     * @return array<string, int> ['2025-10' => 4, ...]
     */
    private static function perMonth(iterable $dates, Carbon $from, Carbon $until): array
    {
        $months = [];

        for ($month = $from->copy()->startOfMonth(); $month->lte($until->copy()->min(now())); $month->addMonth()) {
            $months[$month->format('Y-m')] = 0;
        }

        foreach ($dates as $date) {
            if (isset($months[$date->format('Y-m')])) {
                $months[$date->format('Y-m')]++;
            }
        }

        return $months;
    }
}
