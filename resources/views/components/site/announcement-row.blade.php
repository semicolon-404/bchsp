{{--
    One announcement in a list: the mark of its type (a symbol in a colour, so the kind is known at a glance),
    then its title with marks for "pinned" and "has files", its type by name, and its date and time.
    The home page can show it as a plain list instead (resources/js/news-view.js): its title and when.
--}}
@props(['announcement'])

<li class="border-b border-line last:border-b-0">
    <a href="{{ route('announcements.show', $announcement) }}" class="group flex items-start gap-3 px-4 py-3 no-underline">
        <span class="news-detail">
        <x-site.type-mark :type="$announcement->type" class="mt-0.5" />
        <span class="min-w-0 flex-1">
            {{-- On one line: a longer title ends in an ellipsis, so more announcements fit; the whole of it is one press away. --}}
            <span class="relative block truncate text-ink group-hover:text-brand" title="{{ $announcement->localTitle() }}">
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
            <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 font-mono text-xs text-muted">
                @if ($announcement->type)
                    <span style="color: {{ $announcement->type->colour() }}">{{ $announcement->type->localName() }}</span>
                @endif
                <time datetime="{{ ($announcement->starts_at ?? $announcement->created_at)->toIso8601String() }}">{{ $announcement->shownOn() }} · {{ ($announcement->starts_at ?? $announcement->created_at)->format('H:i') }}{{ app()->getLocale() === 'th' ? ' น.' : '' }}</time>
            </span>
            {{-- An album (an event's photographs): the first few small in a row, and how many more, so it reads as one. --}}
            @if ($photos = $announcement->photos())
                <span class="album-strip" aria-label="{{ trans_choice('{1} :count photograph|[2,*] :count photographs', count($photos), ['count' => count($photos)]) }}">
                    @foreach (array_slice($photos, 0, 4) as $photo)
                        <img src="{{ $photo }}" alt="" loading="lazy">
                    @endforeach
                    @if (count($photos) > 4)
                        <span class="album-more">+{{ count($photos) - 4 }}</span>
                    @endif
                </span>
            @endif
        </span>
        {{-- Its "one page" picture, small: a list of announcements with pictures is easier to scan. --}}
        @if ($image = $announcement->imageUrl())
            <img src="{{ $image }}" alt="" loading="lazy" class="size-14 shrink-0 rounded-md border border-line object-cover">
        @endif
        </span>
        {{-- As a list (the visitor's choice on the home page): as a news reader's, the title on one line and when, nothing else. --}}
        <span class="news-line">
            <span class="news-line-title">{{ $announcement->localTitle() }}</span>
            <span class="news-line-when">
                @if ($announcement->type)
                    <span style="color: {{ $announcement->type->colour() }}">{{ $announcement->type->localName() }}</span> ·
                @endif
                {{ $announcement->shownOn() }} · {{ ($announcement->starts_at ?? $announcement->created_at)->format('H:i') }}{{ app()->getLocale() === 'th' ? ' น.' : '' }}
            </span>
        </span>
    </a>
</li>
