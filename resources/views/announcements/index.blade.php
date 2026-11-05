{{--
    /announcements: the hospital's newsroom. Every announcement that is out, pinned ones first, then the newest; it
    is also the archive: one that has ended stays here.

    A thin band with the search; the kinds as chips held under the top bar, the filters folded away; then the newest
    of what is being looked at (all, or one kind) set large, and the list of the rest beside what is read most, what
    is coming up and who has been posting. Searched, or narrowed by department or by when, it is the list alone: the
    reader is after something by then.

    Shown as rows, or as cards with each one's cover (?view=cards): the choice is in the address, and kept from page to page.
--}}
@php
    // The address of this list with one thing changed, the rest kept; the page number starts over.
    $with = fn (array $change) => route('announcements.index', array_filter(array_merge(['type' => $type?->slug, 'department' => $department?->slug, 'year' => $year ?? $period, 'from' => $from, 'to' => $to, 'q' => $term, 'view' => $look === 'cards' ? 'cards' : null], $change), fn ($value) => filled($value)));
    $narrowed = $type || $department || $year || $period || $term !== '';
    $aside = $mostRead->isNotEmpty() || $coming->isNotEmpty() || $posting->isNotEmpty();
    // How much the kind being looked at holds, or everything.
    $held = $type ? (int) ($counts[$type->id] ?? 0) : $stats['all'];
@endphp
<x-layouts.site :title="($type ? $type->localName() : __('Announcements')).($year ? ' '.$year : '')" :description="__('Announcements, procurement notices and job openings from :name.', ['name' => App\Support\SiteSettings::name()])">
    {{-- A thin band: what this is, how much it holds and the search; under it the kinds as chips, and the filters folded away. --}}
    <section class="border-b border-line bg-surface">
        <div class="wrap pb-3 pt-6 sm:pt-8">
            <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
                <div class="min-w-0">
                    <h1 class="text-3xl leading-tight sm:text-4xl">{{ $type ? $type->localName() : __('Announcements from the hospital') }}</h1>
                    <p class="mt-1 font-mono text-sm text-muted">{{ trans_choice('{0} No announcements yet|{1} :count announcement|[2,*] :count announcements', $held, ['count' => number_format($held)]) }}@if ($fresh) · <span class="text-brand">{{ trans_choice('{1} :count new this week|[2,*] :count new this week', $fresh, ['count' => number_format($fresh)]) }}</span>@endif</p>
                </div>

                {{-- Sent with GET: the address of what was found can be shared, and nothing typed here is kept by the site. --}}
                <form action="{{ route('announcements.index') }}" method="get" role="search" class="flex w-full gap-2 sm:w-[26rem]">
                    @foreach (array_filter(['type' => $type?->slug, 'department' => $department?->slug, 'year' => $year ?? $period, 'from' => $from, 'to' => $to, 'view' => $look === 'cards' ? 'cards' : null]) as $kept => $value)
                        <input type="hidden" name="{{ $kept }}" value="{{ $value }}">
                    @endforeach
                    <label class="relative block min-w-0 flex-1">
                        <span class="sr-only">{{ __('Word in the title') }}</span>
                        <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-3.5 top-1/2 size-5 -translate-y-1/2 text-muted" aria-hidden="true" />
                        <input type="search" name="q" value="{{ $term }}" maxlength="80" placeholder="{{ __('e.g. medicine, dental, interview') }}"
                               class="min-h-11 w-full rounded-full border border-line bg-page pl-11 pr-4 text-ink placeholder:text-muted">
                    </label>
                    <button class="btn btn-brand rounded-full">{{ __('Search') }}</button>
                </form>
            </div>
        </div>
    </section>

    {{-- The kinds, each with how many it holds, and the filters: held under the top bar as the list scrolls, as an app's tabs. --}}
    <div class="news-tabs">
        <div class="wrap flex items-center gap-2 py-2">
            @if ($types->isNotEmpty())
                <nav class="-ml-4 flex min-w-0 flex-1 gap-2 overflow-x-auto py-1 pl-4 [scrollbar-width:none] sm:ml-0 sm:pl-0" aria-label="{{ __('Types') }}">
                    <a href="{{ $with(['type' => null]) }}" @class(['chip shrink-0 gap-2', 'is-on' => ! $type]) aria-current="{{ $type ? 'false' : 'page' }}">
                        <span>{{ __('All') }}</span>
                        <span class="font-mono text-xs opacity-70">{{ number_format($stats['all']) }}</span>
                    </a>
                    @foreach ($types as $one)
                        <a href="{{ $with(['type' => $one->slug, 'year' => $period]) }}" @class(['chip shrink-0 gap-2', 'is-on' => $type?->is($one)]) aria-current="{{ $type?->is($one) ? 'page' : 'false' }}">
                            <span class="size-2 shrink-0 rounded-full" style="background: {{ $one->colour() }}" aria-hidden="true"></span>
                            <span>{{ $one->localName() }}</span>
                            <span class="font-mono text-xs opacity-70">{{ number_format($counts[$one->id] ?? 0) }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif

            {{-- Narrowing by who put it out and by when: a panel under the button on a computer, a sheet from the bottom on a phone. --}}
            <details data-fold class="news-filters shrink-0">
                <summary @class(['chip gap-2', 'is-on' => $department || $year || $period])>
                    <x-heroicon-m-adjustments-horizontal class="size-4" aria-hidden="true" />
                    <span class="hidden sm:inline">{{ __('Filters') }}</span>
                    <span class="sr-only sm:hidden">{{ __('Filters') }}</span>
                    @if ($filtering = (int) (bool) $department + (int) (bool) ($year || $period))
                        <span class="font-mono text-xs">{{ $filtering }}</span>
                    @endif
                </summary>
                <form action="{{ route('announcements.index') }}" method="get" class="news-filters-panel group">
                    <p class="mb-1 flex items-center justify-between text-lg font-semibold sm:hidden">{{ __('Filters') }}</p>
                    @foreach (array_filter(['type' => $type?->slug, 'q' => $term, 'view' => $look === 'cards' ? 'cards' : null]) as $kept => $value)
                        <input type="hidden" name="{{ $kept }}" value="{{ $value }}">
                    @endforeach
                    @if ($departments->isNotEmpty())
                        <label class="block">
                            <span class="mb-1 block text-sm text-muted">{{ __('Department') }}</span>
                            <select name="department" class="min-h-11 w-full rounded-md border border-line bg-page px-3 text-ink">
                                <option value="">{{ __('Every department') }}</option>
                                @foreach ($departments as $one)
                                    <option value="{{ $one->slug }}" @selected($department?->is($one))>{{ $one->localName() }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <label class="block">
                        <span class="mb-1 block text-sm text-muted">{{ __('Period') }}</span>
                        <select name="year" class="min-h-11 w-full rounded-md border border-line bg-page px-3 text-ink">
                            <option value="">{{ __('Any time') }}</option>
                            @foreach (App\Http\Controllers\AnnouncementController::PERIODS as $one => $label)
                                @continue($one === 'custom')
                                <option value="{{ $one }}" @selected($one === $period)>{{ __($label) }}</option>
                            @endforeach
                            <optgroup label="{{ __('Fiscal year') }}">
                                @foreach ($years as $one => $count)
                                    <option value="{{ $one }}" @selected($one === $year)>{{ $one }} ({{ number_format($count) }})</option>
                                @endforeach
                            </optgroup>
                            <option value="custom" @selected($period === 'custom')>{{ __('Choose the dates') }}…</option>
                        </select>
                    </label>
                    {{-- The two dates take room only while "Choose the dates" is picked: by the browser at once, and by the server after sending. --}}
                    <div @class(['gap-3 group-has-[option[value=custom]:checked]:flex', 'flex' => $period === 'custom', 'hidden' => $period !== 'custom'])>
                        <label class="block min-w-0 flex-1">
                            <span class="mb-1 block text-sm text-muted">{{ __('From date') }}</span>
                            <input type="date" name="from" value="{{ $from }}" class="min-h-11 w-full rounded-md border border-line bg-page px-3 text-ink">
                        </label>
                        <label class="block min-w-0 flex-1">
                            <span class="mb-1 block text-sm text-muted">{{ __('To date') }}</span>
                            <input type="date" name="to" value="{{ $to }}" class="min-h-11 w-full rounded-md border border-line bg-page px-3 text-ink">
                        </label>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button class="btn btn-brand flex-1"><x-heroicon-m-funnel class="size-5" aria-hidden="true" />{{ __('Show') }}</button>
                        @if ($narrowed)
                            <a href="{{ $with(['type' => null, 'department' => null, 'year' => null, 'from' => null, 'to' => null, 'q' => null]) }}" class="btn btn-line">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>
            </details>
        </div>
    </div>

    <div class="wrap py-8 sm:py-10">
        {{-- The newest of what is being looked at, set large: one leading with its picture, three beside it. Left out of the list below. --}}
        @if ($featured->isNotEmpty())
            <section aria-labelledby="latest-heading" class="mb-10">
                <h2 id="latest-heading" class="sr-only">{{ __('Latest') }}</h2>
                <div @class(['grid gap-4', 'lg:grid-cols-5' => $featured->count() > 1])>
                    @php($lead = $featured->first())
                    {{-- The picture whole over its own colours blurred (a poster is mostly words, none of it cut off), what it is under it. --}}
                    <a href="{{ route('announcements.show', $lead) }}" @class(['card group flex flex-col overflow-hidden p-0 text-ink no-underline', 'lg:col-span-3' => $featured->count() > 1])>
                        @if ($image = $lead->imageUrl())
                            <span class="news-lead-picture">
                                <img src="{{ $image }}" alt="" aria-hidden="true" class="news-lead-backdrop">
                                <img src="{{ $image }}" alt="" class="relative size-full object-contain transition duration-500 group-hover:scale-[1.02]">
                            </span>
                        @else
                            <x-site.cover :announcement="$lead" class="aspect-[16/9]" large />
                        @endif
                        <span class="block p-5 sm:p-6">
                            @if ($lead->type)
                                <span class="inline-flex items-center gap-2 font-mono text-xs font-semibold" style="color: {{ $lead->type->colour() }}"><span class="size-2 rounded-full" style="background: {{ $lead->type->colour() }}" aria-hidden="true"></span>{{ $lead->type->localName() }}</span>
                            @endif
                            <span class="mt-2 line-clamp-3 block text-xl font-semibold leading-snug group-hover:text-brand sm:line-clamp-2 sm:text-[1.75rem]">{{ $lead->localTitle() }}</span>
                            <span class="mt-2 block font-mono text-sm text-muted">{{ $lead->shownAt() }}@if ($lead->department?->is_active) · {{ $lead->department->localName() }}@endif</span>
                        </span>
                    </a>

                    @if ($featured->count() > 1)
                        <ul class="card flex flex-col divide-y divide-line p-0 lg:col-span-2">
                            @foreach ($featured->slice(1) as $one)
                                <li class="flex-1">
                                    <a href="{{ route('announcements.show', $one) }}" class="group flex h-full items-center gap-4 p-4 text-ink no-underline">
                                        @if ($image = $one->imageUrl())
                                            <img src="{{ $image }}" alt="" loading="lazy" class="aspect-[4/3] w-28 shrink-0 rounded-lg object-cover sm:w-32">
                                        @else
                                            <x-site.cover :announcement="$one" class="aspect-[4/3] w-28 shrink-0 rounded-lg sm:w-32" plain />
                                        @endif
                                        <span class="min-w-0 flex-1">
                                            @if ($one->type)
                                                <span class="block font-mono text-xs font-semibold" style="color: {{ $one->type->colour() }}">{{ $one->type->localName() }}</span>
                                            @endif
                                            <span class="mt-1 line-clamp-2 block font-medium leading-snug group-hover:text-brand">{{ $one->localTitle() }}</span>
                                            <span class="mt-1.5 block font-mono text-xs text-muted">{{ $one->shownAt() }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        @endif

        <div @class(['grid items-start gap-10', 'lg:grid-cols-3' => $aside])>
            <div @class(['min-w-0', 'lg:col-span-2' => $aside])>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-muted" role="status">@if ($featured->isNotEmpty())<span class="font-semibold text-ink">{{ __('Earlier') }}</span> · @endif{{ trans_choice('{0} No announcements match.|{1} :count announcement|[2,*] :count announcements', $announcements->total(), ['count' => number_format($announcements->total())]) }}</p>
                    <nav class="flex gap-2" aria-label="{{ __('Way of looking') }}">
                        <a href="{{ $with(['view' => null, 'page' => $announcements->currentPage() > 1 ? $announcements->currentPage() : null]) }}" @class(['chip gap-2', 'is-on' => $look === 'list']) aria-current="{{ $look === 'list' ? 'true' : 'false' }}"><x-heroicon-m-list-bullet class="size-4" aria-hidden="true" />{{ __('Rows') }}</a>
                        <a href="{{ $with(['view' => 'cards', 'page' => $announcements->currentPage() > 1 ? $announcements->currentPage() : null]) }}" @class(['chip gap-2', 'is-on' => $look === 'cards']) aria-current="{{ $look === 'cards' ? 'true' : 'false' }}"><x-heroicon-m-squares-2x2 class="size-4" aria-hidden="true" />{{ __('Cards') }}</a>
                    </nav>
                </div>

                @if ($announcements->isNotEmpty())
                    @if ($look === 'cards')
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
                    <x-site.empty class="mt-3" icon="magnifying-glass">
                        {{ __('Nothing matches. Try another word, or clear the filters.') }}
                        <a href="{{ $with(['type' => null, 'department' => null, 'year' => null, 'from' => null, 'to' => null, 'q' => null]) }}" class="btn btn-line mx-auto mt-4 flex w-fit">{{ __('See every announcement') }}</a>
                    </x-site.empty>
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
