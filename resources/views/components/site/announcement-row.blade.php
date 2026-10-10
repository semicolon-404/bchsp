{{-- One announcement in a list: its date, type and title on a line, with marks for "pinned" and "has files". --}}
@props(['announcement'])

<li class="border-b border-line last:border-b-0">
    <a href="{{ route('announcements.show', $announcement) }}" class="group flex flex-col gap-1 px-4 py-3 no-underline sm:flex-row sm:items-baseline sm:gap-4">
        <time datetime="{{ ($announcement->starts_at ?? $announcement->created_at)->toDateString() }}" class="shrink-0 font-mono text-sm text-muted sm:w-28">{{ $announcement->shownOn() }}</time>
        <span class="min-w-0 flex-1 text-ink group-hover:text-brand">
            @if ($announcement->isPinnedNow())
                <span class="mr-1 inline-flex items-center gap-1 rounded border border-brand px-1.5 text-xs font-semibold text-brand"><x-heroicon-s-bookmark class="size-3" aria-hidden="true" />{{ __('Pinned') }}</span>
            @endif
            {{ $announcement->localTitle() }}
            @if ($announcement->hasDocuments())
                <x-heroicon-m-paper-clip class="ml-1 inline size-4 text-muted" aria-hidden="true" /><span class="sr-only">{{ __('Has files attached') }}</span>
            @endif
        </span>
        @if ($announcement->type)
            <span class="shrink-0 font-mono text-xs text-syn-variable">{{ $announcement->type->localName() }}</span>
        @endif
    </a>
</li>
