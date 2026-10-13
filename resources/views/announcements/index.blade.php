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
    $with = fn (array $change) => route('announcements.index', array_filter(array_merge(['type' => $type?->slug, 'department' => $department?->slug, 'year' => $year ?? $period, 'from' => $from, 'to' => $to, 'q' => $term, 'view' => $look === 'cards' ? 'cards' : null], $change), fn ($value) => filled($value)));
    $narrowed = $type || $department || $year || $period || $term !== '';
    $aside = $mostRead->isNotEmpty() || $coming->isNotEmpty() || $posting->isNotEmpty();
@endphp
<x-layouts.site :title="($type ? $type->localName() : __('Announcements')).($year ? ' '.$year : '')" :description="__('Announcements, procurement notices and job openings from :name.', ['name' => App\Support\SiteSettings::name()])">
    {{-- The band at the top: what this is, the search, and how much there is. --}}
    <section class="home-screen !min-h-0 border-b border-line">
        <div class="wrap py-10 sm:py-14">
            <p class="eyebrow">{{ __('Announcements') }}</p>
            <h1 class="hero-name mt-2 text-3xl sm:text-5xl">{{ $type ? $type->localName() : __('Announcements from the hospital') }}</h1>
            <p class="mt-3 max-w-2xl text-lg text-muted">{{ __('News, notices, health knowledge and what the hospital has been doing: the newest first, and everything before it kept.') }}</p>

            {{-- Sent with GET: the address of what was found can be shared, and nothing typed here is kept by the site. --}}
            <form action="{{ route('announcements.index') }}" method="get" role="search" class="mt-6 flex max-w-2xl gap-2">
                @foreach (array_filter(['type' => $type?->slug, 'department' => $department?->slug, 'year' => $year ?? $period, 'from' => $from, 'to' => $to, 'view' => $look === 'cards' ? 'cards' : null]) as $kept => $value)
                    <input type="hidden" name="{{ $kept }}" value="{{ $value }}">
                @endforeach
                <label class="relative block min-w-0 flex-1">
                    <span class="sr-only">{{ __('Word in the title') }}</span>
                    <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-muted" aria-hidden="true" />
                    <input type="search" name="q" value="{{ $term }}" maxlength="80" placeholder="{{ __('e.g. medicine, dental, interview') }}"
                           class="min-h-12 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-ink placeholder:text-muted">
                </label>
                <button class="btn btn-brand">{{ __('Search') }}</button>
            </form>

            <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-4">
                @foreach ([['In all', $stats['all']], ['This week', $stats['week']], ['This month', $stats['month']]] as [$label, $figure])
                    <div>
                        <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($figure) }}</dd>
                        <dt class="text-sm text-muted">{{ __($label) }}</dt>
                    </div>
                @endforeach
            </dl>
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
                                    <span class="mt-2 block font-mono text-sm {{ $image ? 'text-white/80' : 'text-muted' }}">{{ $one->shownOn() }}@if ($one->department?->is_active) · {{ $one->department->localName() }}@endif</span>
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
                                    <span class="mt-2 block font-mono text-xs text-muted">{{ $one->shownOn() }}</span>
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
                <a href="{{ $with(['type' => null]) }}" @class(['card flex w-36 shrink-0 flex-col gap-2 px-4 py-4 text-ink no-underline sm:w-auto', 'border-brand bg-raised' => ! $type]) aria-current="{{ $type ? 'false' : 'page' }}">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-raised text-brand" aria-hidden="true"><x-heroicon-o-squares-2x2 class="size-5" /></span>
                    <span class="font-medium leading-tight">{{ __('All') }}</span>
                    <span class="font-mono text-sm text-muted">{{ number_format($stats['all']) }}</span>
                </a>
                @foreach ($types as $one)
                    <a href="{{ $with(['type' => $one->slug, 'year' => $period]) }}" @class(['card flex w-36 shrink-0 flex-col gap-2 px-4 py-4 text-ink no-underline sm:w-auto', 'border-brand bg-raised' => $type?->is($one)]) aria-current="{{ $type?->is($one) ? 'page' : 'false' }}">
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
                    @foreach (array_filter(['type' => $type?->slug, 'q' => $term, 'view' => $look === 'cards' ? 'cards' : null]) as $kept => $value)
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
