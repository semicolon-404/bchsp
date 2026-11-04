{{--
    One announcement in a list, made to be read at a glance: its date large on the left in the colour of its
    kind, then its kind and marks ("new", "pinned") on a line of their own, its title in large letters on at
    most two lines, and nothing else: the posters beside the list carry the pictures.
    The kind is always written out: never colour alone. Thai needs room above and below for its marks: lines are loose.
--}}
@props(['announcement'])
@php
    $date = $announcement->starts_at ?? $announcement->created_at;
    $local = $date->copy()->locale(app()->getLocale());
@endphp

<li class="news-row" @if ($announcement->type) style="--kind: {{ $announcement->type->colour() }}" @endif>
    <a href="{{ route('announcements.show', $announcement) }}" class="group flex items-center gap-4 px-4 py-4 no-underline sm:px-5">
        <time datetime="{{ $date->toDateString() }}" class="news-date" title="{{ $announcement->shownOn() }}">
            <span class="text-2xl font-bold leading-none">{{ $local->day }}</span>
            <span class="mt-1 text-sm font-semibold">{{ $local->translatedFormat('M') }}</span>
        </time>
        <span class="min-w-0 flex-1">
            <span class="flex flex-wrap items-center gap-2">
                @if ($announcement->type)
                    <span class="kind-pill">{{ $announcement->type->localName() }}</span>
                @endif
                @if ($announcement->isNew())
                    <span class="rounded-full bg-brand px-2.5 py-0.5 text-sm font-semibold text-on-brand">{{ __('New') }}</span>
                @endif
                @if ($announcement->isPinnedNow())
                    <span class="inline-flex items-center gap-1 text-sm font-medium text-muted"><x-heroicon-s-bookmark class="size-3.5" aria-hidden="true" />{{ __('Pinned') }}</span>
                @endif
                @if ($announcement->hasDocuments())
                    <span class="inline-flex items-center gap-1 text-sm text-muted"><x-heroicon-m-paper-clip class="size-3.5" aria-hidden="true" />{{ __('Has files attached') }}</span>
                @endif
            </span>
            <span class="mt-2 line-clamp-2 text-[1.1875rem] font-semibold leading-[1.6] tracking-[0.005em] text-ink group-hover:text-brand">{{ $announcement->localTitle() }}</span>
        </span>
    </a>
</li>
