{{--
    One announcement in a list, compact so that many fit beside the posters: its date first, in a column of
    its own where the eye finds it (the day large, the month and year under it), then its title and, in small
    type, its kind (a dot of its colour before it) and the marks "new" and "pinned".
    The kind is always written out: never colour alone. Thai needs room above and below for its marks.
--}}
@props(['announcement'])
@php
    $date = $announcement->starts_at ?? $announcement->created_at;
    $local = $date->copy()->locale(app()->getLocale());
    $year = app()->getLocale() === 'th' ? $date->year + 543 : $date->year;
@endphp

<li class="news-row" @if ($announcement->type) style="--kind: {{ $announcement->type->colour() }}" @endif>
    <a href="{{ route('announcements.show', $announcement) }}" class="group flex items-start gap-4 px-4 py-3 no-underline sm:px-5">
        <time datetime="{{ $date->toDateString() }}" class="news-date">
            <span class="news-day">{{ $local->day }}</span>
            <span class="news-month">{{ $local->translatedFormat('M') }} {{ substr((string) $year, -2) }}</span>
        </time>
        <span class="min-w-0 flex-1">
            <span class="line-clamp-2 text-[0.9375rem] font-medium leading-[1.6] text-ink group-hover:text-brand">{{ $announcement->localTitle() }}</span>
            <span class="news-meta mt-0.5">
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
