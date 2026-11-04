{{--
    One announcement in a list, compact so that many fit beside the posters: its date first as a leaf of a
    calendar (the month on a band of colour, the day large under it), then its title and, in small type, the
    time it was put out, its kind (a dot of its colour before it) and the marks "new" and "pinned".
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
        @if ($sameDay)
            <span class="news-leaf-gap" aria-hidden="true"></span>
        @else
            <time datetime="{{ $date->toDateString() }}" class="news-leaf" title="{{ $announcement->shownOn() }}">
                <span class="news-leaf-month">{{ $local->translatedFormat('M') }} {{ substr((string) $year, -2) }}</span>
                <span class="news-leaf-day">{{ $local->day }}</span>
            </time>
        @endif
        <span class="min-w-0 flex-1">
            <span class="line-clamp-2 text-[0.9375rem] font-medium leading-[1.6] text-ink group-hover:text-brand">{{ $announcement->localTitle() }}</span>
            <span class="news-meta mt-0.5">
                <span class="inline-flex items-center gap-1 font-medium text-ink"><x-heroicon-m-clock class="size-3.5 text-muted" aria-hidden="true" />{{ $local->format('H:i') }} {{ app()->getLocale() === 'th' ? 'น.' : '' }}</span>
                @if ($announcement->type)
                    <span class="news-kind">{{ $announcement->type->localName() }}</span>
                @endif
                @if ($announcement->isNew())
                    <span class="font-semibold text-brand">{{ __('New') }}</span>
                @endif
                @if ($announcement->isPinnedNow())
                    <span class="inline-flex items-center gap-1"><x-heroicon-s-bookmark class="size-3" aria-hidden="true" />{{ __('Pinned') }}</span>
                @endif
                @if ($announcement->hasDocuments())
                    <span class="inline-flex items-center gap-1"><x-heroicon-m-paper-clip class="size-3" aria-hidden="true" /><span class="sr-only">{{ __('Has files attached') }}</span></span>
                @endif
            </span>
        </span>
    </a>
</li>
