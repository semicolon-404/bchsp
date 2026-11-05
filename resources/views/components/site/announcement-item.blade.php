{{--
    One announcement in the list of them all (announcements/index): the day it came out as a leaf of a calendar,
    then its kind by name and colour, its title with marks for "new", "pinned" and "has files", how its text begins,
    who put it out, and its picture small. The whole row is the link. (The home page's shorter list uses
    announcement-row.)
--}}
@props(['announcement'])
@php
    $day = $announcement->starts_at ?? $announcement->created_at;
    $begins = Str::limit(trim((string) preg_replace('/[\s\x{00a0}]+/u', ' ', html_entity_decode(strip_tags($announcement->inLanguage('body')), ENT_QUOTES | ENT_HTML5))), 150);
@endphp

<li class="border-b border-line last:border-b-0">
    <a href="{{ route('announcements.show', $announcement) }}" class="group flex items-start gap-4 px-4 py-4 no-underline hover:bg-raised">
        {{-- The day, read at a glance down the list; the full date is in the line under the title. --}}
        <span class="hidden w-14 shrink-0 flex-col items-center rounded-lg border border-line bg-page py-1.5 text-center sm:flex" aria-hidden="true">
            <span class="font-mono text-2xl font-semibold leading-none text-ink">{{ $day->day }}</span>
            <span class="mt-1 font-mono text-xs uppercase text-muted">{{ $day->locale(app()->getLocale())->translatedFormat('M') }}</span>
        </span>

        <span class="min-w-0 flex-1">
            <span class="flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-xs text-muted">
                @if ($announcement->type)
                    <span class="inline-flex items-center gap-1 font-semibold" style="color: {{ $announcement->type->colour() }}"><x-site.type-mark :type="$announcement->type" bare />{{ $announcement->type->localName() }}</span>
                @endif
                <time datetime="{{ $day->toIso8601String() }}">{{ $announcement->shownAt() }}</time>
                @if ($announcement->department?->is_active)
                    <span>{{ $announcement->department->localName() }}</span>
                @endif
            </span>

            <span class="mt-1.5 block text-lg font-medium leading-snug text-ink group-hover:text-brand">
                @if ($announcement->isNew())
                    <span class="mr-1 inline-flex items-center rounded bg-brand px-1.5 font-sans text-xs font-semibold text-on-brand">{{ __('New') }}</span>
                @endif
                @if ($announcement->isPinnedNow())
                    <span class="mr-1 inline-flex items-center gap-1 rounded border border-brand px-1.5 text-xs font-semibold text-brand"><x-heroicon-s-bookmark class="size-3" aria-hidden="true" />{{ __('Pinned') }}</span>
                @endif
                {{ $announcement->localTitle() }}
                @if ($announcement->hasDocuments())
                    <x-heroicon-m-paper-clip class="ml-1 inline size-4 text-muted" aria-hidden="true" /><span class="sr-only">{{ __('Has files attached') }}</span>
                @endif
            </span>

            @if ($begins !== '')
                <span class="mt-1 line-clamp-2 block text-sm text-muted">{{ $begins }}</span>
            @endif
        </span>

        @if ($image = $announcement->imageUrl())
            <img src="{{ $image }}" alt="" loading="lazy" class="size-16 shrink-0 rounded-lg border border-line object-cover sm:size-20">
        @endif
    </a>
</li>
