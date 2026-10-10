{{--
    One announcement in a list: the mark of its type (a symbol in a colour, so the kind is known at a glance),
    then its title with marks for "pinned" and "has files", its type by name, and its date.
--}}
@props(['announcement'])

<li class="border-b border-line last:border-b-0">
    <a href="{{ route('announcements.show', $announcement) }}" class="group flex items-start gap-3 px-4 py-3 no-underline">
        <x-site.type-mark :type="$announcement->type" class="mt-0.5" />
        <span class="min-w-0 flex-1">
            <span class="block text-ink group-hover:text-brand">
                @if ($announcement->isPinnedNow())
                    <span class="mr-1 inline-flex items-center gap-1 rounded border border-brand px-1.5 text-xs font-semibold text-brand"><x-heroicon-s-bookmark class="size-3" aria-hidden="true" />{{ __('Pinned') }}</span>
                @endif
                {{ $announcement->localTitle() }}
                @if ($announcement->hasDocuments())
                    <x-heroicon-m-paper-clip class="ml-1 inline size-4 text-muted" aria-hidden="true" /><span class="sr-only">{{ __('Has files attached') }}</span>
                @endif
            </span>
            <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 font-mono text-xs text-muted">
                @if ($announcement->type)
                    <span style="color: {{ $announcement->type->colour() }}">{{ $announcement->type->localName() }}</span>
                @endif
                <time datetime="{{ ($announcement->starts_at ?? $announcement->created_at)->toDateString() }}">{{ $announcement->shownOn() }}</time>
            </span>
        </span>
        {{-- Its "one page" picture, small: a list of announcements with pictures is easier to scan. --}}
        @if ($image = $announcement->imageUrl())
            <img src="{{ $image }}" alt="" loading="lazy" class="size-14 shrink-0 rounded-md border border-line object-cover">
        @endif
    </a>
</li>
