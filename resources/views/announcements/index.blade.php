{{--
    /announcements: the hospital's newsroom. Every announcement that is out, pinned ones first, then the newest; it
    is also the archive: one that has ended stays here.

    As it opens: a band with the search and a few figures, the three newest set large, a tile for each kind with how
    many it holds, then the list beside what is read most, what is coming up and who has been posting. Narrowed (by
    kind, by the department that put it out, by when it came out, or by a word in the title) it is the list alone
    with its filters: the reader is after something by then.

    Shown as rows, or as cards with each one's cover (?view=cards): the choice is in the address, and kept from page to page.
--}}
@php
    // The address of this list with one thing changed, the rest kept; the page number starts over.
    $with = fn (array $change) => route('announcements.index', array_filter(array_merge(['type' => $type?->slug, 'department' => $department?->slug, 'year' => $year ?? $period, 'from' => $from, 'to' => $to, 'q' => $term, 'view' => $look !== 'list' ? $look : null, 'by' => $look === 'index' && $by !== $byDefault ? $by : null], $change), fn ($value) => filled($value)));
    $narrowed = $type || $department || $year || $period || $term !== '';
    $aside = $mostRead->isNotEmpty() || $coming->isNotEmpty() || $posting->isNotEmpty();
@endphp
<x-layouts.site :title="($type ? $type->localName() : __('Announcements')).($year ? ' '.$year : '')" :description="__('Announcements, procurement notices and job openings from :name.', ['name' => App\Support\SiteSettings::name()])">
    {{-- The band at the top: what this is, the search, and how much there is. --}}
    <section class="news-band home-screen !min-h-0 border-b border-line" @if ($type) style="--kind: {{ $type->colour() }}" @endif>
        {{-- The kind's own symbol, large and faint, at the far side: the page changes with what is chosen. --}}
        <x-dynamic-component :component="'heroicon-o-'.($type ? $type->icon() : 'megaphone')" class="news-band-mark" aria-hidden="true" />
        <div class="wrap relative grid grid-cols-[minmax(0,1fr)] items-center gap-8 py-10 sm:py-14 lg:grid-cols-[minmax(0,1fr)_21rem] lg:gap-14">
            <div class="min-w-0">
            <h1 class="hero-name mt-2 text-3xl [overflow-wrap:anywhere] sm:text-5xl">{{ $type ? $type->localName() : __('Announcements from the hospital') }}</h1>
            <p class="mt-3 max-w-2xl text-lg text-muted">{{ __(match ($type?->slug) {
                'news' => 'News and notices from the hospital, the newest first.',
                'procurement' => 'Procurement, invitations to bid and their results, by fiscal year.',
                'jobs' => 'Job openings at the hospital and the results of each selection.',
                'knowledge' => "Health knowledge from the hospital's staff, to read and to share.",
                'activities' => 'What the hospital has been doing, in pictures.',
                default => 'News, notices, health knowledge and what the hospital has been doing: the newest first, and everything before it kept.',
            }) }}</p>

            {{-- Sent with GET: the address of what was found can be shared, and nothing typed here is kept by the site. --}}
            <form action="{{ route('announcements.index') }}" method="get" role="search" class="mt-6 max-w-3xl">
                @foreach (array_filter(['type' => $type?->slug, 'department' => $department?->slug, 'year' => $year ?? $period, 'from' => $from, 'to' => $to, 'view' => $look !== 'list' ? $look : null, 'by' => $look === 'index' && $by !== $byDefault ? $by : null]) as $kept => $value)
                    <input type="hidden" name="{{ $kept }}" value="{{ $value }}">
                @endforeach
                <label class="news-search relative block min-w-0 flex-1">
                    <span class="sr-only">{{ __('Word in the title') }}</span>
                    <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 size-6 -translate-y-1/2 text-muted" aria-hidden="true" />
                    <input type="search" name="q" value="{{ $term }}" maxlength="80" placeholder="{{ __('e.g. medicine, dental, interview') }}"
                           class="min-h-14 w-full rounded-2xl border border-line bg-surface pl-13 pr-28 text-lg text-ink shadow-sm placeholder:text-muted">
                    <button class="btn btn-brand absolute right-1.5 top-1/2 min-h-11 -translate-y-1/2 rounded-xl px-5">{{ __('Search') }}</button>
                </label>
            </form>

            {{-- What the list is narrowed by, said in words: the ways to change it are all in one place below. --}}
            @if ($narrowed)
                <p class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-muted" role="status">
                    <x-heroicon-o-funnel class="size-4 text-brand" aria-hidden="true" />
                    <span>{{ __('Showing') }}</span>
                    <span class="font-semibold text-ink">{{ collect([
                        $type?->localName(),
                        $department?->localName(),
                        $year ? __('Fiscal year :year', ['year' => $year]) : ($period ? __(App\Http\Controllers\AnnouncementController::PERIODS[$period]) : null),
                        $term !== '' ? '“'.$term.'”' : null,
                    ])->filter()->implode(' · ') }}</span>
                </p>
            @endif

            </div>

            {{-- How much there is and how fresh: a pane of its own beside the search, for the eye that wants the size of things. --}}
            <aside class="news-band-pane" aria-label="{{ __('In figures') }}">
                <dl class="grid grid-cols-3 gap-3 lg:grid-cols-1 lg:gap-0 lg:divide-y lg:divide-line">
                    @foreach ([['In all', $figures['all'], 'rectangle-stack'], ['This week', $figures['week'], 'sparkles'], ['This month', $figures['month'], 'calendar-days']] as [$label, $figure, $icon])
                        <div class="flex flex-col items-start gap-1 lg:flex-row-reverse lg:items-center lg:gap-4 lg:py-3.5">
                            <dd class="font-mono text-3xl font-semibold text-ink lg:text-2xl" data-count>{{ number_format($figure) }}</dd>
                            <dt class="flex items-center gap-2 text-sm text-muted lg:flex-1"><x-dynamic-component :component="'heroicon-o-'.$icon" class="hidden size-5 text-brand lg:block" aria-hidden="true" />{{ __($label) }}</dt>
                        </div>
                    @endforeach
                </dl>
                @if ($newest)
                    <a href="{{ route('announcements.show', $newest) }}" class="group mt-4 flex items-center gap-3 rounded-xl border border-line bg-page px-3 py-2.5 text-ink no-underline">
                        <span class="relative flex size-2.5 shrink-0" aria-hidden="true"><span class="absolute inline-flex size-full animate-ping rounded-full bg-brand opacity-60 motion-reduce:hidden"></span><span class="relative inline-flex size-2.5 rounded-full bg-brand"></span></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs text-muted">{{ __('Last updated') }} · {{ $newest->shownAt() }}</span>
                            <span class="block truncate text-sm font-medium group-hover:text-brand">{{ $newest->localTitle() }}</span>
                        </span>
                        <x-heroicon-m-chevron-right class="size-4 shrink-0 text-brand" aria-hidden="true" />
                    </a>
                @endif
            </aside>
        </div>
    </section>

    <div class="wrap py-10 sm:py-14">
        {{-- The three newest, set large: only as the whole list opens. No marks on them: they are said again in the list below. --}}
        @if ($featured->isNotEmpty())
            <section aria-labelledby="latest-heading">
                <h2 id="latest-heading" class="eyebrow">{{ __('Latest') }}</h2>
                <div @class(['mt-4 grid gap-4', 'lg:grid-cols-5' => $featured->count() > 1])>
                    @foreach ($featured as $one)
                        @if ($loop->first)
                            <a href="{{ route('announcements.show', $one) }}" @class(['card group relative flex min-h-72 flex-col justify-end overflow-hidden p-0 text-ink no-underline', 'lg:col-span-3 lg:row-span-2' => $featured->count() > 1])>
                                @if ($image = $one->imageUrl())
                                    <img src="{{ $image }}" alt="" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-105">
                                    <span class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent" aria-hidden="true"></span>
                                @else
                                    <x-site.cover :announcement="$one" class="absolute inset-0" large />
                                @endif
                                <span class="relative block p-5 sm:p-6 {{ $image ? 'text-white' : '' }}">
                                    @if ($one->type)
                                        <span class="inline-block rounded px-2 py-0.5 font-mono text-xs font-semibold {{ $image ? 'bg-white/20 text-white backdrop-blur' : 'bg-page' }}" @unless ($image) style="color: {{ $one->type->colour() }}" @endunless>{{ $one->type->localName() }}</span>
                                    @endif
                                    <span class="mt-2 block text-2xl font-semibold leading-snug sm:text-3xl">{{ $one->localTitle() }}</span>
                                    <span class="mt-2 block font-mono text-sm {{ $image ? 'text-white/80' : 'text-muted' }}">{{ $one->shownAt() }}@if ($one->department?->is_active) · {{ $one->department->localName() }}@endif</span>
                                </span>
                            </a>
                        @else
                            <a href="{{ route('announcements.show', $one) }}" class="card group flex gap-4 overflow-hidden p-0 text-ink no-underline lg:col-span-2">
                                @if ($image = $one->imageUrl())
                                    <img src="{{ $image }}" alt="" loading="lazy" class="w-28 shrink-0 object-cover sm:w-36">
                                @else
                                    <x-site.cover :announcement="$one" class="w-28 shrink-0 sm:w-36" plain />
                                @endif
                                <span class="min-w-0 flex-1 py-4 pr-4">
                                    @if ($one->type)
                                        <span class="block font-mono text-xs font-semibold" style="color: {{ $one->type->colour() }}">{{ $one->type->localName() }}</span>
                                    @endif
                                    <span class="mt-1 line-clamp-3 block text-lg font-medium leading-snug group-hover:text-brand">{{ $one->localTitle() }}</span>
                                    <span class="mt-2 block font-mono text-xs text-muted">{{ $one->shownAt() }}</span>
                                </span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        {{-- A tile for each kind, with how many it holds. --}}
        @if ($types->isNotEmpty())
            {{-- On a phone they are a row that slides sideways: six tiles stacked would push the list a screen further down. --}}
            <nav @class(['-mx-4 flex gap-3 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:grid sm:grid-cols-3 sm:overflow-visible sm:px-0 lg:grid-cols-6', 'mt-10' => $featured->isNotEmpty()]) aria-label="{{ __('Types') }}">
                <a href="{{ $with(['type' => null]) }}" @class(['kind-pick card flex w-36 shrink-0 flex-col gap-2 px-4 py-4 text-ink no-underline sm:w-auto', 'is-on' => ! $type]) aria-current="{{ $type ? 'false' : 'page' }}">
                    @unless ($type)<span class="kind-tick" aria-hidden="true"><x-heroicon-m-check class="size-4" /></span>@endunless
                    <span class="flex size-9 items-center justify-center rounded-lg bg-raised text-brand" aria-hidden="true"><x-heroicon-o-squares-2x2 class="size-5" /></span>
                    <span class="font-medium leading-tight">{{ __('All') }}</span>
                    <span class="font-mono text-sm text-muted">{{ number_format($stats['all']) }}</span>
                </a>
                @foreach ($types as $one)
                    <a href="{{ $with(['type' => $one->slug]) }}" @class(['kind-pick card flex w-36 shrink-0 flex-col gap-2 px-4 py-4 text-ink no-underline sm:w-auto', 'is-on' => $type?->is($one)]) style="--kind: {{ $one->colour() }}" aria-current="{{ $type?->is($one) ? 'page' : 'false' }}">
                        @if ($type?->is($one))<span class="kind-tick" aria-hidden="true"><x-heroicon-m-check class="size-4" /></span>@endif
                        <x-site.type-mark :type="$one" />
                        <span class="font-medium leading-tight">{{ $one->localName() }}</span>
                        <span class="font-mono text-sm text-muted">{{ number_format($counts[$one->id] ?? 0) }}</span>
                    </a>
                @endforeach
            </nav>
        @endif

        <div @class(['mt-10 grid items-start gap-10', 'lg:grid-cols-3' => $aside])>
            <div @class(['min-w-0', 'lg:col-span-2' => $aside])>
                {{-- Narrowing the list: by who put it out and by when. The word is kept from the search above. --}}
                <form action="{{ route('announcements.index') }}" method="get" class="card group flex flex-wrap items-end gap-3 px-4 py-4">
                {{-- The fiscal years, newest first, one press each: what an office looks for most after the kind. --}}
                @if (count($years) > 0)
                    <nav class="flex w-full flex-wrap items-center gap-2" aria-label="{{ __('Fiscal year') }}">
                        <span class="mr-1 inline-flex items-center gap-1.5 text-sm font-semibold text-ink"><x-heroicon-o-calendar class="size-5 text-brand" aria-hidden="true" />{{ __('Fiscal year') }}</span>
                        <a href="{{ $with(['year' => null, 'from' => null, 'to' => null]) }}" @class(['chip', 'is-on' => ! $year]) aria-current="{{ $year ? 'false' : 'true' }}">{{ __('All') }}</a>
                        @foreach (array_slice($years, 0, 5, true) as $one => $count)
                            <a href="{{ $with(['year' => $one, 'from' => null, 'to' => null]) }}" @class(['chip gap-1.5', 'is-on' => $one === $year]) aria-current="{{ $one === $year ? 'true' : 'false' }}">{{ $one }}<span class="font-mono text-xs opacity-60">{{ number_format($count) }}</span></a>
                        @endforeach
                    </nav>
                @endif

                    @foreach (array_filter(['type' => $type?->slug, 'q' => $term, 'view' => $look !== 'list' ? $look : null, 'by' => $look === 'index' && $by !== $byDefault ? $by : null]) as $kept => $value)
                        <input type="hidden" name="{{ $kept }}" value="{{ $value }}">
                    @endforeach
                    @if ($departments->isNotEmpty())
                        <label class="block min-w-0 max-w-full">
                            <span class="mb-1 block text-sm text-muted">{{ __('Department') }}</span>
                            <select name="department" class="min-h-11 max-w-full rounded-md border border-line bg-page px-3 text-ink">
                                <option value="">{{ __('Every department') }}</option>
                                @foreach ($departments as $one)
                                    <option value="{{ $one->slug }}" @selected($department?->is($one))>{{ $one->localName() }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <label class="block">
                        <span class="mb-1 block text-sm text-muted">{{ __('Period') }}</span>
                        <select name="year" class="min-h-11 rounded-md border border-line bg-page px-3 text-ink">
                            <option value="{{ $year }}">{{ $year ? __('Fiscal year :year', ['year' => $year]) : __('Any time') }}</option>
                            @if ($year)
                                <option value="">{{ __('Any time') }}</option>
                            @endif
                            @foreach (App\Http\Controllers\AnnouncementController::PERIODS as $one => $label)
                                @continue($one === 'custom')
                                <option value="{{ $one }}" @selected($one === $period)>{{ __($label) }}</option>
                            @endforeach
                            <option value="custom" @selected($period === 'custom')>{{ __('Choose the dates') }}…</option>
                        </select>
                    </label>
                    {{-- The two dates take room only while "Choose the dates" is picked: by the browser at once, and by the server after sending. --}}
                    <div @class(['w-full items-end gap-3 sm:w-auto group-has-[option[value=custom]:checked]:flex', 'flex' => $period === 'custom', 'hidden' => $period !== 'custom'])>
                        <label class="block min-w-0 flex-1">
                            <span class="mb-1 block text-sm text-muted">{{ __('From date') }}</span>
                            <input type="date" name="from" value="{{ $from }}" class="min-h-11 w-full rounded-md border border-line bg-page px-3 text-ink">
                        </label>
                        <label class="block min-w-0 flex-1">
                            <span class="mb-1 block text-sm text-muted">{{ __('To date') }}</span>
                            <input type="date" name="to" value="{{ $to }}" class="min-h-11 w-full rounded-md border border-line bg-page px-3 text-ink">
                        </label>
                    </div>
                    <button class="btn btn-line"><x-heroicon-m-funnel class="size-5" aria-hidden="true" />{{ __('Show') }}</button>
                    @if ($narrowed)
                        <a href="{{ $with(['type' => null, 'department' => null, 'year' => null, 'from' => null, 'to' => null, 'q' => null]) }}" class="btn btn-line">{{ __('Clear') }}</a>
                    @endif
                </form>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-muted" role="status">{{ trans_choice('{0} No announcements match.|{1} :count announcement|[2,*] :count announcements', $announcements->total(), ['count' => number_format($announcements->total())]) }}</p>
                    <nav class="flex gap-2" aria-label="{{ __('Way of looking') }}">
                        <a href="{{ $with(['view' => 'list', 'by' => null, 'page' => $announcements->currentPage() > 1 ? $announcements->currentPage() : null]) }}" @class(['chip gap-2', 'is-on' => $look === 'list']) aria-current="{{ $look === 'list' ? 'true' : 'false' }}"><x-heroicon-m-list-bullet class="size-4" aria-hidden="true" />{{ __('Rows') }}</a>
                        <a href="{{ $with(['view' => 'cards', 'by' => null, 'page' => $announcements->currentPage() > 1 ? $announcements->currentPage() : null]) }}" @class(['chip gap-2', 'is-on' => $look === 'cards']) aria-current="{{ $look === 'cards' ? 'true' : 'false' }}"><x-heroicon-m-squares-2x2 class="size-4" aria-hidden="true" />{{ __('Cards') }}</a>
                        <a href="{{ $with(['view' => 'index', 'by' => null, 'page' => null]) }}" @class(['chip gap-2', 'is-on' => $look === 'index']) aria-current="{{ $look === 'index' ? 'true' : 'false' }}"><x-heroicon-m-bars-3-bottom-left class="size-4" aria-hidden="true" />{{ __('Index') }}</a>
                    </nav>
                </div>

                {{-- The index gathers its titles under a heading for each day, week, month, year or fiscal year: the reader picks. --}}
                @if ($look === 'index')
                    <nav class="mt-4 flex flex-wrap items-center gap-2" aria-label="{{ __('Group by') }}">
                        <span class="mr-1 text-sm font-semibold text-ink">{{ __('Group by') }}</span>
                        @foreach (App\Http\Controllers\AnnouncementController::GROUPS as $one => $label)
                            <a href="{{ $with(['by' => $one === $byDefault ? null : $one, 'page' => null]) }}" @class(['chip', 'is-on' => $by === $one]) aria-current="{{ $by === $one ? 'true' : 'false' }}">{{ __($label) }}</a>
                        @endforeach
                    </nav>
                @endif

                @if ($announcements->isNotEmpty())
                    @if ($look === 'index')
                        @php
                            $th = app()->getLocale() === 'th';
                            $era = fn ($date) => $date->year + ($th ? 543 : 0);
                            $local = fn ($date) => $date->copy()->locale(app()->getLocale());
                            // The heading each title falls under, by the grouping picked.
                            $heading = fn ($date) => match ($by) {
                                'day' => $local($date)->translatedFormat('l j F ').$era($date),
                                'week' => __('Week of :from – :to', ['from' => $local($date->copy()->startOfWeek())->translatedFormat('j M'), 'to' => $local($date->copy()->endOfWeek())->translatedFormat('j M ').$era($date->copy()->endOfWeek())]),
                                'month' => $local($date)->translatedFormat('F ').$era($date),
                                'year' => ($th ? 'พ.ศ. ' : '').$era($date),
                                default => __('Fiscal year :year', ['year' => App\Models\Announcement::fiscalYearOf($date)]),
                            };
                            $groups = $announcements->getCollection()->groupBy(fn ($one) => $heading($one->starts_at ?? $one->created_at));
                        @endphp
                        <div class="toc mt-4">
                            @foreach ($groups as $title => $ones)
                                <section aria-label="{{ $title }}">
                                    <h3 class="toc-head"><span>{{ $title }}</span><span class="font-mono text-xs font-normal text-muted">{{ trans_choice('{1} :count announcement|[2,*] :count announcements', $ones->count(), ['count' => $ones->count()]) }}</span></h3>
                                    <ol>
                                        @foreach ($ones as $one)
                                            @php($at = $one->starts_at ?? $one->created_at)
                                            <li>
                                                <a href="{{ route('announcements.show', $one) }}" class="toc-row">
                                                    <span class="size-2 shrink-0 rounded-full" style="background: {{ $one->type?->colour() ?? 'var(--color-muted)' }}" title="{{ $one->type?->localName() }}" aria-hidden="true"></span>
                                                    <span class="min-w-0 flex-1 truncate">{{ $one->localTitle() }}</span>
                                                    @if ($one->type)<span class="sr-only">({{ $one->type->localName() }})</span>@endif
                                                    <time datetime="{{ $at->toIso8601String() }}" class="shrink-0 font-mono text-xs text-muted">{{ $by === 'day' ? '' : $local($at)->translatedFormat('j M').' · ' }}{{ $at->format('H:i') }}{{ $th ? ' น.' : '' }}</time>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ol>
                                </section>
                            @endforeach
                        </div>
                    @elseif ($look === 'cards')
                        <ul @class(['mt-3 grid gap-4 sm:grid-cols-2', 'lg:grid-cols-3' => ! $aside])>
                            @foreach ($announcements as $announcement)
                                <li><x-site.announcement-card :announcement="$announcement" /></li>
                            @endforeach
                        </ul>
                    @else
                        <ul class="card mt-3 p-0">
                            @foreach ($announcements as $announcement)
                                <x-site.announcement-item :announcement="$announcement" />
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-10">{{ $announcements->links('components.site.pagination') }}</div>
                @else
                    <x-site.empty class="mt-3" icon="magnifying-glass">{{ __('Nothing matches. Try another word, or clear the filters.') }}</x-site.empty>
                @endif
            </div>

            {{-- Beside the list as it opens: ways in for someone who came with nothing in mind. --}}
            @if ($aside)
                <aside class="space-y-8">
                    @if ($mostRead->isNotEmpty())
                        <section aria-labelledby="most-read-heading">
                            <h2 id="most-read-heading" class="flex items-center gap-2 text-xl"><x-heroicon-o-fire class="size-5 text-brand" aria-hidden="true" />{{ __('Most read') }}</h2>
                            <ol class="card mt-3 p-0">
                                @foreach ($mostRead as $one)
                                    <li class="border-b border-line last:border-b-0">
                                        <a href="{{ route('announcements.show', $one) }}" class="group flex items-start gap-3 px-4 py-3 no-underline">
                                            <span class="w-6 shrink-0 text-center font-mono text-xl font-semibold text-brand" aria-hidden="true">{{ $loop->iteration }}</span>
                                            <span class="min-w-0 flex-1">
                                                <span class="line-clamp-2 block text-ink group-hover:text-brand">{{ $one->localTitle() }}</span>
                                                <span class="mt-0.5 block font-mono text-xs text-muted">{{ trans_choice('{1} opened :count time|[2,*] opened :count times', $one->views, ['count' => number_format($one->views)]) }}</span>
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endif

                    @if ($coming->isNotEmpty())
                        <section aria-labelledby="coming-heading">
                            <h2 id="coming-heading" class="flex items-center gap-2 text-xl"><x-heroicon-o-calendar-days class="size-5 text-brand" aria-hidden="true" />{{ __('Coming up') }}</h2>
                            <ul class="card mt-3 p-0">
                                @foreach ($coming as $one)
                                    <li class="border-b border-line last:border-b-0">
                                        <a href="{{ route('announcements.show', $one) }}" class="group flex items-center gap-3 px-4 py-3 no-underline">
                                            <span class="flex w-12 shrink-0 flex-col items-center rounded-lg border border-line bg-page py-1 text-center" aria-hidden="true">
                                                <span class="font-mono text-xl font-semibold leading-none text-brand">{{ $one->event_on->day }}</span>
                                                <span class="mt-0.5 font-mono text-xs text-muted">{{ $one->event_on->locale(app()->getLocale())->translatedFormat('M') }}</span>
                                            </span>
                                            <span class="line-clamp-2 min-w-0 flex-1 text-ink group-hover:text-brand">{{ $one->localTitle() }}<span class="sr-only"> ({{ $one->event_on->locale(app()->getLocale())->translatedFormat('j F') }})</span></span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <p class="mt-3 text-sm"><a href="{{ route('calendar') }}" class="font-semibold text-brand underline">{{ __('Calendar') }} &rarr;</a></p>
                        </section>
                    @endif

                    @if ($posting->isNotEmpty())
                        <section aria-labelledby="posting-heading">
                            <h2 id="posting-heading" class="flex items-center gap-2 text-xl"><x-heroicon-o-building-office-2 class="size-5 text-brand" aria-hidden="true" />{{ __('From the departments') }}</h2>
                            <ul class="mt-3 space-y-2">
                                @foreach ($posting as $one)
                                    <li>
                                        <a href="{{ $with(['department' => $one->slug]) }}" class="card flex items-center gap-3 px-3 py-2 text-ink no-underline">
                                            <x-site.department-avatar :department="$one" class="size-9 text-sm" />
                                            <span class="min-w-0 flex-1 truncate">{{ $one->localName() }}</span>
                                            <x-heroicon-m-chevron-right class="size-4 shrink-0 text-brand" aria-hidden="true" />
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </aside>
            @endif
        </div>

        <p class="mt-12 text-sm text-muted">
            <a href="{{ route('feed') }}" class="inline-flex items-center gap-1 underline"><x-heroicon-m-rss class="size-4" aria-hidden="true" />{{ __('Follow new announcements (RSS)') }}</a>
        </p>
    </div>
</x-layouts.site>
