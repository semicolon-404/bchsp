{{--
    One unit of the hospital in a grid: its cover as a band across the top (or the site's colour), its round
    picture over the edge of it, its name and the line under it, where it is, its number, and whether it is open
    this minute when its lines of the clinic schedule were read with it. The whole card is the link.
--}}
@props(['department'])
@php
    // Only when the caller read the schedule with the departments: the card itself asks the database nothing.
    $sessions = $department->relationLoaded('sessions') ? $department->sessions->where('is_active', true) : collect();
    $open = $sessions->contains(fn ($session) => $session->isOpenNow());
@endphp

<a href="{{ route('departments.show', $department) }}" class="card group flex h-full flex-col overflow-hidden p-0 text-ink no-underline">
    @if ($cover = $department->imageUrl())
        <span class="block h-24 overflow-hidden bg-raised"><img src="{{ $cover }}" alt="" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105"></span>
    @else
        <span class="block h-16 bg-gradient-to-br from-brand/40 to-raised" aria-hidden="true"></span>
    @endif

    <span class="flex flex-1 flex-col px-5 pb-5">
        <x-site.department-avatar :department="$department" class="relative -mt-7 size-14 border-4 !border-surface text-2xl" />
        <span class="mt-3 block text-lg font-medium leading-snug group-hover:text-brand">{{ $department->localName() }}</span>
        @if (filled($department->localHeadline()))
            <span class="mt-1 line-clamp-2 block text-sm text-muted">{{ $department->localHeadline() }}</span>
        @endif

        <span class="mt-3 flex-1 space-y-1.5 text-sm text-muted">
            @if ($department->location)
                <span class="flex items-start gap-2"><x-heroicon-m-map-pin class="mt-0.5 size-4 shrink-0 text-brand" aria-hidden="true" /><span>{{ $department->location }}</span></span>
            @endif
            @if ($department->phone)
                <span class="flex items-start gap-2"><x-heroicon-m-phone class="mt-0.5 size-4 shrink-0 text-brand" aria-hidden="true" /><span class="font-mono">{{ $department->phone }}</span></span>
            @endif
            @if ($department->hours)
                <span class="flex items-start gap-2"><x-heroicon-m-clock class="mt-0.5 size-4 shrink-0 text-brand" aria-hidden="true" /><span class="line-clamp-2">{{ $department->hours }}</span></span>
            @endif
        </span>

        <span class="mt-4 flex items-center justify-between gap-3 border-t border-line pt-3 text-sm">
            @if ($sessions->isNotEmpty())
                {{-- Said in words as well as by the dot. --}}
                <span class="inline-flex items-center gap-2 font-medium {{ $open ? 'text-syn-function' : 'text-muted' }}">
                    <span class="size-2 rounded-full {{ $open ? 'bg-syn-function' : 'bg-muted' }}" aria-hidden="true"></span>{{ $open ? __('Open now') : __('Closed now') }}
                </span>
            @else
                <span></span>
            @endif
            <span class="inline-flex items-center gap-1 font-semibold text-brand">{{ __('See the profile') }}<x-heroicon-m-arrow-right class="size-4 transition group-hover:translate-x-1" aria-hidden="true" /></span>
        </span>
    </span>
</a>
