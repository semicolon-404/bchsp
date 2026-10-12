{{--
    /announcements: every announcement that is out, pinned ones first, then the newest. It is also the archive:
    one that has ended stays here. Narrowed by type (the row of chips), by the department that put it out, by when it came out (a fiscal year, today,
    this week, this month, or two dates of the reader's own) and by a word in the title.
    Shown as rows, or as cards with each one's cover (?view=cards): the choice is in the address, and kept from page to page.
--}}
@php
    // The address of this list with one thing changed, the rest kept; the page number starts over.
    $with = fn (array $change) => route('announcements.index', array_filter(array_merge(['type' => $type?->slug, 'department' => $department?->slug, 'year' => $year ?? $period, 'from' => $from, 'to' => $to, 'q' => $term, 'view' => $look === 'cards' ? 'cards' : null], $change), fn ($value) => filled($value)));
@endphp
<x-layouts.site :title="($type ? $type->localName() : __('Announcements')).($year ? ' '.$year : '')" :description="__('Announcements, procurement notices and job openings from :name.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap py-10 sm:py-14">
        <p class="eyebrow">{{ __('Announcements') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ $type ? $type->localName() : __('Announcements from the hospital') }}</h1>

        @if ($types->isNotEmpty())
            <nav class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('Types') }}">
                <a href="{{ $with(['type' => null]) }}" @class(['chip', 'is-on' => ! $type]) aria-current="{{ $type ? 'false' : 'page' }}">{{ __('All') }}</a>
                @foreach ($types as $one)
                    <a href="{{ $with(['type' => $one->slug, 'year' => $period]) }}" @class(['chip gap-2', 'is-on' => $type?->is($one)]) aria-current="{{ $type?->is($one) ? 'page' : 'false' }}"><x-site.type-mark :type="$one" bare />{{ $one->localName() }}</a>
                @endforeach
            </nav>
        @endif

        {{-- Sent with GET: the address of what was found can be shared, and nothing typed here is kept by the site. --}}
        <form action="{{ route('announcements.index') }}" method="get" role="search" class="group mt-4 flex flex-wrap items-end gap-3">
            @if ($type)
                <input type="hidden" name="type" value="{{ $type->slug }}">
            @endif
            @if ($look === 'cards')
                <input type="hidden" name="view" value="cards">
            @endif
            @if ($departments->isNotEmpty())
                <label class="block min-w-0 max-w-full">
                    <span class="mb-1 block text-sm text-muted">{{ __('Department') }}</span>
                    <select name="department" class="min-h-11 max-w-full rounded-md border border-line bg-surface px-3 text-ink">
                        <option value="">{{ __('Every department') }}</option>
                        @foreach ($departments as $one)
                            <option value="{{ $one->slug }}" @selected($department?->is($one))>{{ $one->localName() }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label class="block">
                <span class="mb-1 block text-sm text-muted">{{ __('Period') }}</span>
                <select name="year" class="min-h-11 rounded-md border border-line bg-surface px-3 text-ink">
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
                    <input type="date" name="from" value="{{ $from }}" class="min-h-11 w-full rounded-md border border-line bg-surface px-3 text-ink">
                </label>
                <label class="block min-w-0 flex-1">
                    <span class="mb-1 block text-sm text-muted">{{ __('To date') }}</span>
                    <input type="date" name="to" value="{{ $to }}" class="min-h-11 w-full rounded-md border border-line bg-surface px-3 text-ink">
                </label>
            </div>
            <label class="block min-w-0 flex-1 basis-56">
                <span class="mb-1 block text-sm text-muted">{{ __('Word in the title') }}</span>
                <input type="search" name="q" value="{{ $term }}" maxlength="80" placeholder="{{ __('e.g. medicine, dental, interview') }}"
                       class="min-h-11 w-full rounded-md border border-line bg-surface px-3 text-ink placeholder:text-muted">
            </label>
            <button class="btn btn-brand"><x-heroicon-m-funnel class="size-5" aria-hidden="true" />{{ __('Show') }}</button>
            @if ($year || $period || $department || $term !== '')
                <a href="{{ $with(['department' => null, 'year' => null, 'from' => null, 'to' => null, 'q' => null]) }}" class="btn btn-line">{{ __('Clear') }}</a>
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
                <ul class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($announcements as $announcement)
                        <li><x-site.announcement-card :announcement="$announcement" /></li>
                    @endforeach
                </ul>
            @else
                <ul class="card mt-3 p-0">
                    @foreach ($announcements as $announcement)
                        <x-site.announcement-row :announcement="$announcement" />
                    @endforeach
                </ul>
            @endif

            <div class="mt-10">{{ $announcements->links('components.site.pagination') }}</div>
        @endif
    </div>
</x-layouts.site>
