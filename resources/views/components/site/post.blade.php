{{--
    One announcement as a post in a department's timeline (departments/show) and in the feed of all of them
    (posts.blade.php, "linked"): who put it out and how long ago, its title and the start of its text, then its
    pictures, as a social page shows a post. An album of photographs shows as tiles that open in the photo viewer
    (resources/js/lightbox.js); a poster shows whole over its own colours. Under it, a bar to read on, to share it,
    and how often it has been read. In the feed the department's name leads to its own page.
--}}
@props(['announcement', 'department', 'linked' => false])
@php
    $text = Str::limit(trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($announcement->inLanguage('body'))))), 220);
    $photos = $announcement->photos();
    $image = $announcement->imageUrl();
    $url = route('announcements.show', $announcement);
@endphp

<li class="post">
    <div class="flex items-start gap-3 px-4 pt-4 sm:px-5">
        <x-site.department-avatar :department="$department" class="size-11 text-base" />
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold leading-snug">
                @if ($linked)
                    <a href="{{ route('departments.show', $department) }}" class="text-ink no-underline hover:text-brand">{{ $department->localName() }}</a>
                @else
                    {{ $department->localName() }}
                @endif
            </p>
            <p class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-sm text-muted">
                <time datetime="{{ ($announcement->starts_at ?? $announcement->created_at)->toIso8601String() }}">{{ $announcement->shownAt() }}</time>
                @if ($announcement->type)
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('announcements.index', ['type' => $announcement->type->slug]) }}" class="inline-flex items-center gap-1.5 no-underline hover:underline" style="color: {{ $announcement->type->colour() }}">
                        <span class="size-1.5 rounded-full" style="background: {{ $announcement->type->colour() }}" aria-hidden="true"></span>{{ $announcement->type->localName() }}
                    </a>
                @endif
            </p>
        </div>
        @if ($announcement->pinned_in_department)
            <span class="mt-1 shrink-0 text-brand" title="{{ __('Pinned') }}"><x-heroicon-s-bookmark class="size-5" aria-hidden="true" /><span class="sr-only">{{ __('Pinned') }}</span></span>
        @endif
    </div>

    <div class="px-4 pb-3 pt-3 sm:px-5">
        <h3 class="text-lg font-semibold leading-snug"><a href="{{ $url }}" class="text-ink no-underline hover:text-brand">{{ $announcement->localTitle() }}</a></h3>
        @if ($text !== '')
            <p class="mt-1.5 line-clamp-3 text-ink/90">{{ $text }}</p>
        @endif
    </div>

    @if (count($photos) > 1)
        {{-- An album: up to four tiles, the last saying how many more; each opens the photo viewer on the whole album. --}}
        <ul @class(['post-photos', 'is-two' => count($photos) === 2, 'is-three' => count($photos) === 3]) data-gallery data-gallery-title="{{ $announcement->localTitle() }}" data-gallery-kind="{{ $announcement->type?->localName() }}" data-gallery-kind-colour="{{ $announcement->type?->colour() }}" data-gallery-date="{{ $announcement->shownAt() }}">
            @foreach ($photos as $photo)
                <li @class(['hidden' => $loop->iteration > 4])>
                    <a href="{{ $photo }}" target="_blank" rel="noopener" data-lightbox>
                        <img src="{{ $photo }}" alt="{{ __('Photograph :number of :count', ['number' => $loop->iteration, 'count' => $loop->count]) }}" loading="lazy">
                        @if ($loop->iteration === 4 && $loop->count > 4)
                            <span class="post-photos-more" aria-hidden="true">+{{ $loop->count - 4 }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    @elseif ($image)
        {{-- A poster across the post, as a social page shows one; a very tall one shows its top, the rest a press away. --}}
        <a href="{{ $url }}" class="post-media" tabindex="-1" aria-hidden="true">
            <img src="{{ $image }}" alt="" loading="lazy">
        </a>
    @endif

    {{-- How it has been received, then what can be done with it, as under a post on a social page. --}}
    @if ($announcement->views > 0 || count($photos) > 1 || $announcement->hasDocuments())
        <p class="post-stats">
            @if ($announcement->views > 0)
                <span class="inline-flex items-center gap-1.5"><x-heroicon-s-eye class="size-4" aria-hidden="true" />{{ trans_choice('{1} opened :count time|[2,*] opened :count times', $announcement->views, ['count' => number_format($announcement->views)]) }}</span>
            @endif
            <span class="ml-auto inline-flex items-center gap-3">
                @if (count($photos) > 1)
                    <span>{{ trans_choice('{1} :count photograph|[2,*] :count photographs', count($photos), ['count' => count($photos)]) }}</span>
                @endif
                @if ($announcement->hasDocuments())
                    <span class="inline-flex items-center gap-1"><x-heroicon-m-paper-clip class="size-4" aria-hidden="true" />{{ __('Has files attached') }}</span>
                @endif
            </span>
        </p>
    @endif
    <div class="post-bar">
        <a href="{{ $url }}" class="post-action"><x-heroicon-o-book-open class="size-5" aria-hidden="true" />{{ __('Read') }}</a>
        <button type="button" class="post-action" data-share-url="{{ $url }}" data-share-title="{{ $announcement->localTitle() }}" data-shared="{{ __('Link copied') }}">
            <x-heroicon-o-share class="size-5" aria-hidden="true" /><span data-share-label>{{ __('Share') }}</span>
        </button>
    </div>
</li>
