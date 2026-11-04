{{--
    One announcement in a list, kept quiet as a newsroom's list is: a line of small type above (its kind, a dot
    of its colour before it, and its date), then its title. "New" and "pinned" are small marks beside the kind.
    The kind is always written out: never colour alone. Thai needs room above and below for its marks.
--}}
@props(['announcement'])

<li class="news-row" @if ($announcement->type) style="--kind: {{ $announcement->type->colour() }}" @endif>
    <a href="{{ route('announcements.show', $announcement) }}" class="group px-5 py-4 no-underline sm:px-6">
        <span class="news-meta">
            @if ($announcement->type)
                <span class="news-kind">{{ $announcement->type->localName() }}</span>
            @endif
            <time datetime="{{ ($announcement->starts_at ?? $announcement->created_at)->toDateString() }}">{{ $announcement->shownOn() }}</time>
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
        <span class="mt-1 line-clamp-2 text-[1.0625rem] font-medium leading-[1.65] text-ink group-hover:text-brand">{{ $announcement->localTitle() }}</span>
    </a>
</li>
