{{--
    /announcements/{slug}: one announcement, read as a blog's post: its kind, its title, a byline (who put it out, when,
    how long it takes to read); its picture wide, then its text in a column easy to read, its album and its files,
    with share, copy and print held beside it on a wide screen and a line along the top for how far down the reader is.
    At its foot, who put it out, the one before and after, and a few more of the same kind.
--}}
@php($files = $announcement->files())
@php($links = $announcement->documentLinks())
<x-layouts.site :title="$announcement->localTitle()" :image="$announcement->imageUrl()">
    {{-- Read: the lists stop calling it new for this visitor (resources/js/seen.js). --}}
    <span hidden data-seen-mark="{{ $announcement->id }}"></span>
    {{-- How far down the reader is, as a thin line along the top (resources/js/progress.js). --}}
    <div class="read-progress" data-read-progress aria-hidden="true"></div>

    {{-- The banner: the post's own picture, blurred and darkened, behind its title in white; without one, its kind's colour. --}}
    <header @class(['blog-head blog-hero', 'has-picture' => $announcement->imageUrl()]) style="--kind: {{ $announcement->type?->colour() ?? 'var(--color-brand)' }}">
    @if ($cover = $announcement->imageUrl())
        <img src="{{ $cover }}" alt="" class="blog-hero-backdrop" aria-hidden="true">
    @endif
    <span class="blog-hero-shade" aria-hidden="true"></span>
    <div class="wrap relative max-w-4xl pb-8 pt-8 sm:pb-12 sm:pt-12">
        <p class="text-sm"><a href="{{ route('announcements.index') }}" class="blog-back">&larr; {{ __('All announcements') }}</a></p>

        @if (! $announcement->isPublished())
            {{-- Only staff who are signed in get this far (AnnouncementController::show). --}}
            <p class="mt-6 rounded-lg border border-danger px-4 py-3 text-danger" role="status">{{ __('Not showing to the public right now: only staff who are signed in can see this page.') }}</p>
        @elseif (! $announcement->isCurrent())
            {{-- Kept for the record: a reader should know it is no longer in force. --}}
            <p class="mt-6 rounded-lg border border-line bg-surface px-4 py-3 text-muted" role="status">{{ __('From the archive: this announcement ended on :date.', ['date' => $announcement->ends_at->locale(app()->getLocale())->translatedFormat('j M ').($announcement->ends_at->year + (app()->getLocale() === 'th' ? 543 : 0))]) }}</p>
        @endif

        @if ($announcement->type)
            <p class="mt-6"><a href="{{ route('announcements.index', ['type' => $announcement->type->slug]) }}" class="kind-pill no-underline" style="--kind: {{ $announcement->type->colour() }}">{{ $announcement->type->localName() }}</a></p>
        @endif
        {{-- Its size follows its length: a short title large, a long one a step smaller, never cramped or shouting. --}}
        @php($length = mb_strlen($announcement->localTitle()))
        <h1 @class(['blog-title', 'is-long' => $length > 70 && $length <= 130, 'is-very-long' => $length > 130])>{!! collect(preg_split('/\s+/u', trim($announcement->localTitle())))->map(fn ($run) => '<span class="whole">'.e($run).'</span>')->implode(' ') !!}</h1>

        {{-- The byline: who put it out, when, and how long it takes to read. --}}
        @php($postedAt = $announcement->starts_at ?? $announcement->created_at)
        <div class="blog-byline">
            @if ($announcement->department?->is_active)
                <a href="{{ route('departments.show', $announcement->department) }}" class="flex items-center gap-3 no-underline">
                    <x-site.department-avatar :department="$announcement->department" class="size-11 text-base" />
                    <span>
                        <span class="block font-semibold text-ink">{{ $announcement->department->localName() }}</span>
                        <span class="block text-sm text-muted"><time datetime="{{ $postedAt->toIso8601String() }}">{{ $announcement->shownAt() }}</time> · {{ trans_choice('{1} :count min read|[2,*] :count min read', $announcement->minutesToRead(), ['count' => $announcement->minutesToRead()]) }}</span>
                    </span>
                </a>
            @else
                <span class="text-sm text-muted"><time datetime="{{ $postedAt->toIso8601String() }}">{{ $announcement->shownAt() }}</time> · {{ trans_choice('{1} :count min read|[2,*] :count min read', $announcement->minutesToRead(), ['count' => $announcement->minutesToRead()]) }}</span>
            @endif
            <a href="{{ route('announcements.index', array_filter(['type' => $announcement->type?->slug, 'year' => App\Models\Announcement::fiscalYearOf($postedAt)])) }}" class="ms-auto text-sm text-muted no-underline hover:text-brand">{{ __('Fiscal year :year', ['year' => App\Models\Announcement::fiscalYearOf($postedAt)]) }}</a>
        </div>

        @if ($announcement->event_on)
            {{-- The day of what it is about, which is not the day it was posted. --}}
            <p class="mt-4 inline-flex flex-wrap items-center gap-2 rounded-lg border border-brand px-3 py-2">
                <x-heroicon-m-calendar-days class="size-5 text-brand" aria-hidden="true" />
                <span class="font-medium">{{ __('Takes place on') }}
                    {{ $announcement->event_on->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'th' ? 'l j F ' : 'l j F Y') }}{{ app()->getLocale() === 'th' ? $announcement->event_on->year + 543 : '' }}@if ($announcement->event_until && ! $announcement->event_until->isSameDay($announcement->event_on)) – {{ $announcement->event_until->locale(app()->getLocale())->translatedFormat('j F') }}@endif
                </span>
                <a href="{{ route('calendar', ['month' => $announcement->event_on->format('Y-m')]) }}" class="text-sm text-brand underline">{{ __('Calendar') }}</a>
            </p>
        @endif

    </div>
    </header>

    <div class="blog-body wrap">
    {{-- Beside the text on a wide screen, held in view: share, copy the link, print. --}}
    @if ($announcement->isPublished())
        <aside class="blog-tools" aria-label="{{ __('Share') }}">
            <button type="button" class="blog-tool" data-share-url="{{ $announcement->shortUrl() }}" data-share-title="{{ $announcement->localTitle() }}" data-shared="{{ __('Link copied') }}" title="{{ __('Share') }}"><x-heroicon-o-share class="size-5" aria-hidden="true" /><span class="sr-only" data-share-label>{{ __('Share') }}</span></button>
            <button type="button" class="blog-tool" data-copy="{{ $announcement->shortUrl() }}" data-copied="✓" title="{{ __('Copy link') }}" hidden><x-heroicon-o-link class="size-5" aria-hidden="true" /><span class="sr-only" data-copy-label>{{ __('Copy link') }}</span></button>
            <button type="button" class="blog-tool" data-print title="{{ __('Print') }}" hidden><x-heroicon-o-printer class="size-5" aria-hidden="true" /><span class="sr-only">{{ __('Print') }}</span></button>
        </aside>
    @endif
    <article class="blog-article">
        @if ($image = $announcement->imageUrl())
            {{-- The announcement as one picture, shown whole whatever its shape; a press opens it at full size. --}}
            <a href="{{ $image }}" target="_blank" rel="noopener" class="block" data-lightbox data-lightbox-details="#post-body" data-gallery-kind="{{ $announcement->type?->localName() }}" data-gallery-kind-colour="{{ $announcement->type?->colour() }}" data-gallery-date="{{ $announcement->shownAt() }}">
                <img src="{{ $image }}" alt="{{ $announcement->localTitle() }}" class="mx-auto max-h-[85vh] w-auto max-w-full rounded-xl border border-line">
                <span class="sr-only">{{ __('Open the picture at full size') }}</span>
            </a>
        @endif

        @if ($announcement->hasBody())
            <div class="rich mt-8" id="post-body">{!! $announcement->renderedBody() !!}</div>
        @endif

        @if ($photos = $announcement->photos())
            {{--
                The album as a mosaic: the first photograph large, the next ones beside and under it, and past nine the
                last tile says how many more there are. Every photograph is in the gallery: a press opens the viewer
                with the whole album in a strip (resources/js/lightbox.js); without scripts, each in a tab of its own.
            --}}
            <section class="mt-10" aria-label="{{ __('Photographs') }}">
                <h2 class="flex items-center gap-2 text-lg"><x-heroicon-o-photo class="size-5 text-brand" aria-hidden="true" />{{ trans_choice('{1} :count photograph|[2,*] :count photographs', count($photos), ['count' => count($photos)]) }}</h2>
                <ul @class(['album mt-4', 'is-one' => count($photos) === 1, 'is-two' => count($photos) === 2]) data-gallery data-lightbox-details="#post-body" data-gallery-title="{{ $announcement->localTitle() }}" data-gallery-kind="{{ $announcement->type?->localName() }}" data-gallery-kind-colour="{{ $announcement->type?->colour() }}" data-gallery-date="{{ $announcement->shownAt() }}">
                    @foreach ($photos as $photo)
                        <li @class(['is-hidden' => $loop->iteration > 9])>
                            <a href="{{ $photo }}" target="_blank" rel="noopener" data-lightbox>
                                <img src="{{ $photo }}" alt="{{ __('Photograph :number of :count', ['number' => $loop->iteration, 'count' => $loop->count]) }}" loading="lazy">
                                @if ($loop->iteration === 9 && $loop->count > 9)
                                    <span class="album-more-over" aria-hidden="true">+{{ $loop->count - 9 }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($files || $links)
            <section class="mt-8">
                <h2 class="eyebrow">{{ __('Files') }}</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($files as $file)
                        <li>
                            <a href="{{ $file['url'] }}" target="_blank" rel="noopener" class="card flex items-center gap-3 px-4 py-3 text-ink no-underline">
                                <x-heroicon-o-document-arrow-down class="size-6 shrink-0 text-brand" aria-hidden="true" />
                                <span class="min-w-0 flex-1 break-words">{{ $file['name'] }}</span>
                                <span class="shrink-0 font-mono text-sm text-muted">PDF · {{ $file['size'] }}</span>
                            </a>
                            {{--
                                Read in place, on a screen wide enough to read a page on: opened by the reader, so a phone
                                on a slow line is not made to fetch the file. The browser's own reader shows it.
                            --}}
                            <details class="mt-2 hidden md:block">
                                <summary class="cursor-pointer text-sm font-semibold text-brand underline">{{ __('Read it here') }}</summary>
                                <iframe src="{{ $file['url'] }}#view=FitH" title="{{ $file['name'] }}" loading="lazy" class="mt-2 h-[80vh] w-full rounded-lg border border-line bg-surface"></iframe>
                            </details>
                        </li>
                    @endforeach
                    {{-- Documents kept elsewhere (Google Drive): the reader is told where a press will take them. --}}
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="card flex items-center gap-3 px-4 py-3 text-ink no-underline">
                                <x-heroicon-o-arrow-top-right-on-square class="size-6 shrink-0 text-brand" aria-hidden="true" />
                                <span class="min-w-0 flex-1 break-words">{{ $link['label'] }}</span>
                                <span class="shrink-0 font-mono text-sm text-muted">{{ $link['host'] }}</span>
                                <span class="sr-only">{{ __('(opens in a new tab)') }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
        {{-- An announcement with nothing but its title says so, so the page does not look broken. --}}
        @if (! $announcement->imageUrl() && ! $announcement->hasBody() && ! $announcement->photos() && ! $files && ! $links)
            <p class="text-muted">{{ __('This announcement has no further details.') }}</p>
        @endif

        @if ($announcement->isPublished())
            <div data-helpful><x-site.helpful :announcement="$announcement" /></div>
        @endif
        {{-- Who put it out, and the way to all they have posted. --}}
        @if ($announcement->department?->is_active)
            <div class="blog-author">
                <x-site.department-avatar :department="$announcement->department" class="size-14 text-lg" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-muted">{{ __('Put out by') }}</p>
                    <p class="text-lg font-semibold">{{ $announcement->department->localName() }}</p>
                    @if (filled($announcement->department->localHeadline()))
                        <p class="mt-1 text-sm text-muted">{{ $announcement->department->localHeadline() }}</p>
                    @endif
                </div>
                <a href="{{ route('posts', ['department' => $announcement->department->slug]) }}" class="btn btn-line shrink-0">{{ __('See their posts') }} &rarr;</a>
            </div>
        @endif

        {{-- The one before and after it, to read on in order. --}}
        @if ($before || $after)
            <nav class="blog-pager" aria-label="{{ __('Next and previous') }}">
                @if ($before)
                    <a href="{{ route('announcements.show', $before) }}" class="is-before"><small>&larr; {{ __('Older') }}</small><span>{{ $before->localTitle() }}</span></a>
                @endif
                @if ($after)
                    <a href="{{ route('announcements.show', $after) }}" class="is-after"><small>{{ __('Newer') }} &rarr;</small><span>{{ $after->localTitle() }}</span></a>
                @endif
            </nav>
        @endif
    </article>
    </div>

    {{-- Somewhere to go next: the newest others of the same kind. Words only: the page above may be one large picture already. --}}
    @if ($more->isNotEmpty())
        <section class="blog-more border-t border-line bg-surface">
            <div class="wrap max-w-3xl py-10 sm:py-14">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-2xl">{{ $announcement->type ? __('More: :type', ['type' => $announcement->type->localName()]) : __('More announcements') }}</h2>
                    <a href="{{ route('announcements.index', array_filter(['type' => $announcement->type?->slug])) }}" class="btn btn-line">{{ __('See all') }} &rarr;</a>
                </div>
                <ul class="card mt-5 bg-page p-0">
                    @foreach ($more as $one)
                        <li class="border-b border-line last:border-b-0">
                            <a href="{{ route('announcements.show', $one) }}" class="group flex items-center gap-4 px-4 py-4 no-underline hover:bg-raised">
                                <x-site.type-mark :type="$one->type" />
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium text-ink group-hover:text-brand">{{ $one->localTitle() }}</span>
                                    <span class="mt-0.5 block font-mono text-xs text-muted">{{ $one->shownAt() }}</span>
                                </span>
                                <x-heroicon-m-chevron-right class="size-5 shrink-0 text-brand" aria-hidden="true" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
    {{-- The window that opens from Share. --}}
    <x-site.share-sheet />
</x-layouts.site>
