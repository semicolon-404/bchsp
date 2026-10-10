{{--
    /announcements: every announcement that is out, pinned ones first, then the newest. It is also the archive:
    one that has ended stays here. Narrowed by type (the row of chips), by fiscal year and by a word in the title.
--}}
@php
    // The address of this list with one thing changed, the rest kept; the page number starts over.
    $with = fn (array $change) => route('announcements.index', array_filter(array_merge(['type' => $type?->slug, 'year' => $year, 'q' => $term], $change), fn ($value) => filled($value)));
@endphp
<x-layouts.site :title="($type ? $type->localName() : __('Announcements')).($year ? ' '.$year : '')" :description="__('Announcements, procurement notices and job openings from :name.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap py-10 sm:py-14">
        <p class="eyebrow">{{ __('Announcements') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ $type ? $type->localName() : __('Announcements from the hospital') }}</h1>

        @if ($types->isNotEmpty())
            <nav class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('Types') }}">
                <a href="{{ $with(['type' => null]) }}" @class(['chip', 'is-on' => ! $type]) aria-current="{{ $type ? 'false' : 'page' }}">{{ __('All') }}</a>
                @foreach ($types as $one)
                    <a href="{{ $with(['type' => $one->slug, 'year' => null]) }}" @class(['chip', 'is-on' => $type?->is($one)]) aria-current="{{ $type?->is($one) ? 'page' : 'false' }}">{{ $one->localName() }}</a>
                @endforeach
            </nav>
        @endif

        {{-- Sent with GET: the address of what was found can be shared, and nothing typed here is kept by the site. --}}
        <form action="{{ route('announcements.index') }}" method="get" role="search" class="mt-4 flex flex-wrap items-end gap-3">
            @if ($type)
                <input type="hidden" name="type" value="{{ $type->slug }}">
            @endif
            <label class="block">
                <span class="mb-1 block text-sm text-muted">{{ __('Fiscal year') }}</span>
                <select name="year" class="min-h-11 rounded-md border border-line bg-surface px-3 text-ink">
                    <option value="">{{ __('Every year') }}</option>
                    @foreach ($years as $one => $count)
                        <option value="{{ $one }}" @selected($one === $year)>{{ $one }} ({{ number_format($count) }})</option>
                    @endforeach
                </select>
            </label>
            <label class="block min-w-0 flex-1 basis-56">
                <span class="mb-1 block text-sm text-muted">{{ __('Word in the title') }}</span>
                <input type="search" name="q" value="{{ $term }}" maxlength="80" placeholder="{{ __('e.g. medicine, dental, interview') }}"
                       class="min-h-11 w-full rounded-md border border-line bg-surface px-3 text-ink placeholder:text-muted">
            </label>
            <button class="btn btn-brand"><x-heroicon-m-funnel class="size-5" aria-hidden="true" />{{ __('Show') }}</button>
            @if ($year || $term !== '')
                <a href="{{ $with(['year' => null, 'q' => null]) }}" class="btn btn-line">{{ __('Clear') }}</a>
            @endif
        </form>

        <p class="mt-6 text-sm text-muted" role="status">{{ trans_choice('{0} No announcements match.|{1} :count announcement|[2,*] :count announcements', $announcements->total(), ['count' => number_format($announcements->total())]) }}</p>

        @if ($announcements->isNotEmpty())
            <ul class="card mt-3 p-0">
                @foreach ($announcements as $announcement)
                    <x-site.announcement-row :announcement="$announcement" />
                @endforeach
            </ul>

            <div class="mt-10">{{ $announcements->links('components.site.pagination') }}</div>
        @endif
    </div>
</x-layouts.site>
