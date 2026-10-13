{{--
    One announcement as a card: its cover (the picture staff gave it), then its type, title and date.
    Without a cover one is drawn from its kind and its day (x-site.cover), so that cards in a row stay the same height.
--}}
@props(['announcement'])

<a href="{{ route('announcements.show', $announcement) }}" class="card group flex h-full flex-col overflow-hidden p-0 text-ink no-underline">
    @if ($image = $announcement->imageUrl())
        {{-- From the top: a "one page" announcement has its heading there. --}}
        <img src="{{ $image }}" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover object-top">
    @else
        <x-site.cover :announcement="$announcement" class="aspect-[4/3] w-full" />
    @endif

    <div class="flex flex-1 flex-col p-4">
        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-xs text-muted">
            @if ($announcement->type)
                <span style="color: {{ $announcement->type->colour() }}">{{ $announcement->type->localName() }}</span>
            @endif
            @if ($announcement->isNew())
                <span class="inline-flex items-center rounded bg-brand px-1.5 font-sans text-xs font-semibold text-on-brand">{{ __('New') }}</span>
            @endif
            @if ($announcement->isPinnedNow())
                <span class="inline-flex items-center gap-1 rounded border border-brand px-1.5 font-sans font-semibold text-brand"><x-heroicon-s-bookmark class="size-3" aria-hidden="true" />{{ __('Pinned') }}</span>
            @endif
        </p>
        <h2 class="mt-2 line-clamp-3 flex-1 text-base font-medium group-hover:text-brand">
            {{ $announcement->localTitle() }}
            @if ($announcement->hasDocuments())
                <x-heroicon-m-paper-clip class="ml-1 inline size-4 text-muted" aria-hidden="true" /><span class="sr-only">{{ __('Has files attached') }}</span>
            @endif
        </h2>
        <p class="mt-3 font-mono text-xs text-muted"><time datetime="{{ ($announcement->starts_at ?? $announcement->created_at)->toDateString() }}">{{ $announcement->shownOn() }}</time></p>
    </div>
</a>
