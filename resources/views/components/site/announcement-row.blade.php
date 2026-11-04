{{--
    One announcement in a list, compact so that many fit beside the posters: its date first as a leaf of a
    calendar (the month on a band of colour, the day large under it), then its kind in its colour with the
    time it was put out, and its title on one line.
    With same-day, the leaf is left out: the row above it already shows that day, and this one its time.
    The kind is always written out: never colour alone. Thai needs room above and below for its marks.
--}}
@props(['announcement', 'sameDay' => false])
@php
    $date = $announcement->starts_at ?? $announcement->created_at;
    $local = $date->copy()->locale(app()->getLocale());
    $year = app()->getLocale() === 'th' ? $date->year + 543 : $date->year;
@endphp

<li @class(['news-row', 'is-same-day' => $sameDay]) @if ($announcement->type) style="--kind: {{ $announcement->type->colour() }}" @endif>
    <a href="{{ route('announcements.show', $announcement) }}" class="group flex items-start gap-4 px-4 py-3 no-underline sm:px-5">
        <span class="news-detail">
        @if ($sameDay)
            <span class="news-leaf-gap" aria-hidden="true"></span>
        @else
            <time datetime="{{ $date->toDateString() }}" class="news-leaf" title="{{ $announcement->shownOn() }}">
                <span class="news-leaf-month">{{ $local->translatedFormat('M') }} {{ substr((string) $year, -2) }}</span>
                <span class="news-leaf-day">{{ $local->day }}</span>
            </time>
        @endif
        {{-- Little to read: the kind, clear in its colour, and the title on one line. The rest is one press away. --}}
        <span class="min-w-0 flex-1">
            <span class="flex items-center gap-2">
                @if ($announcement->type)
                    <span class="kind-pill">{{ $announcement->type->localName() }}</span>
                @endif
                @if ($announcement->isNew())
                    <span class="text-xs font-semibold text-brand">{{ __('New') }}</span>
                @endif
                <span class="ml-auto shrink-0 text-xs text-muted">{{ $local->format('H:i') }}{{ app()->getLocale() === 'th' ? ' น.' : '' }}</span>
            </span>
            <span class="mt-1 block truncate text-[0.9375rem] font-medium leading-[1.7] text-ink group-hover:text-brand" title="{{ $announcement->localTitle() }}">{{ $announcement->localTitle() }}</span>
        </span>
        </span>
        {{-- As a list: the title and when, nothing else. --}}
        <span class="news-line">
            <span class="min-w-0 flex-1 truncate text-ink group-hover:text-brand">{{ $announcement->localTitle() }}</span>
            <time datetime="{{ $date->toIso8601String() }}" class="shrink-0 text-xs text-muted tabular-nums">{{ $announcement->shownOn() }} · {{ $local->format('H:i') }}</time>
        </span>
    </a>
</li>
