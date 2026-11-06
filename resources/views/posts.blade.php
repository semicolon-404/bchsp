{{--
    /posts: the feed. What the departments have posted, newest first, each as a post with the department's picture
    and name over it, as on a department's own page but all of them together (AnnouncementController::posts).
    A row of the departments' round pictures narrows it to one (?department=...); beside it, what is read most.

    The list of announcements (/announcements) is for finding one; this is for reading down, to see what the
    hospital's people are doing.
--}}
@php
    $with = fn (?string $slug) => route('posts', array_filter(['department' => $slug]));
@endphp
<x-layouts.site :title="$department ? __('Posts from :name', ['name' => $department->localName()]) : __('Feed')" :description="__('What the departments of :name have posted, newest first.', ['name' => App\Support\SiteSettings::name()])">
    {{--
        The banner, on the hospital's deep green: on the left what this is (the feed, or one department's posts with its
        round picture) and how lively it is; on the right the three newest posts fanned out as cards, the newest in
        front, each its picture or its kind's colour, leaning a little; a press opens one. Still: nothing drifts.
    --}}
    <section class="feed-hero" aria-labelledby="feed-heading">
        <div class="wrap relative grid items-center gap-10 py-12 sm:py-16 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
            <div>
                @if ($department)
                    <p class="flex items-center gap-3">
                        <x-site.department-avatar :department="$department" class="size-12 text-lg ring-2 ring-white/40" />
                        <a href="{{ route('posts') }}" class="feed-hero-back">&larr; {{ __('Every department') }}</a>
                    </p>
                @else
                    <p class="feed-hero-eyebrow">{{ __('Feed') }}</p>
                @endif
                <h1 id="feed-heading" class="feed-hero-title">{{ $department ? $department->localName() : __('From the departments') }}</h1>
                <p class="mt-3 max-w-xl text-lg text-white/85">{{ __('What the hospital\'s departments are doing and saying, newest first.') }}</p>
                <ul class="feed-hero-facts">
                    @if ($latest = $fan->first())
                        <li><span class="feed-hero-live" aria-hidden="true"></span>{{ __('Last post') }} {{ $latest->shownAt() }}</li>
                    @endif
                    <li><b data-count>{{ number_format($posts->total()) }}</b> {{ __('posts') }}</li>
                    @unless ($department)
                        <li><b data-count>{{ number_format($departments->count()) }}</b> {{ __('Departments that posted') }}</li>
                    @endunless
                </ul>
            </div>

            @if ($fan->isNotEmpty())
                <div class="feed-fan" aria-label="{{ __('Latest') }}">
                    @foreach ($fan->reverse()->values() as $one)
                        <a href="{{ route('announcements.show', $one) }}" class="feed-fan-card is-{{ $fan->count() - $loop->index }}" style="--kind: {{ $one->type?->colour() ?? 'var(--color-brand)' }}">
                            @if ($picture = $one->imageUrl())
                                <img src="{{ $picture }}" alt="" @unless ($loop->last) loading="lazy" @endunless>
                            @else
                                <span class="feed-fan-plain" aria-hidden="true"><x-heroicon-o-newspaper class="size-10" /></span>
                            @endif
                            <span class="feed-fan-caption">
                                <small>{{ $one->department?->localName() ?? $one->type?->localName() }}</small>
                                {{ Str::limit($one->localTitle(), 70) }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @if ($departments->isNotEmpty())
        {{-- The departments that have posted, as story cards to press: a ring round those that posted this week. It
             scrolls away with the page (cards this tall held at the top would leave the feed too little room), and
             slides sideways on a phone. --}}
        <nav class="border-b border-line bg-page" aria-label="{{ __('Departments') }}">
            <ul class="wrap flex max-w-5xl gap-2.5 overflow-x-auto py-3 [scrollbar-width:none]">
                <li class="shrink-0">
                    <a href="{{ $with(null) }}" @class(['story-card is-all', 'is-on' => ! $department]) aria-current="{{ $department ? 'false' : 'page' }}">
                        <span class="story-card-face"><x-heroicon-o-squares-2x2 class="size-8" aria-hidden="true" /></span>
                        <span class="story-card-name">{{ __('All') }}</span>
                    </a>
                </li>
                @foreach ($departments as $one)
                    @php($fresh = $one->last_posted && Illuminate\Support\Carbon::parse($one->last_posted)->gte(now()->subWeek()))
                    <li class="shrink-0">
                        <a href="{{ $with($one->slug) }}" @class(['story-card', 'is-on' => $department?->is($one)]) aria-current="{{ $department?->is($one) ? 'page' : 'false' }}">
                            @if ($cover = $covers->get($one->id)?->imageUrl())
                                <img src="{{ $cover }}" alt="" loading="lazy" class="story-card-picture">
                            @endif
                            <span @class(['story-ring', 'is-fresh' => $fresh])><x-site.department-avatar :department="$one" class="size-full text-sm" /></span>
                            <span class="story-card-name">
                                {{ $one->localName() }}
                                @if ($fresh)
                                    <small>{{ Illuminate\Support\Carbon::parse($one->last_posted)->locale(app()->getLocale())->shortRelativeToNowDiffForHumans() }}</small>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    {{-- The feed on a grey ground, the posts white on it, as a social page. --}}
    <div class="bg-raised">
    <div class="wrap grid max-w-5xl items-start gap-10 py-10 sm:py-14 lg:grid-cols-3">
        <div class="min-w-0 lg:col-span-2">
            @if ($posts->isEmpty())
                <x-site.empty icon="newspaper">{{ __('No department has posted anything yet.') }}</x-site.empty>
            @else
                <ul class="space-y-4 sm:space-y-5" data-feed>
                    @foreach ($posts as $announcement)
                        <x-site.post :announcement="$announcement" :department="$announcement->department" linked />
                    @endforeach
                </ul>

                {{-- The next posts fetched onto this page (resources/js/toc.js); without the script, a link to them. --}}
                @if ($posts->hasMorePages())
                    <p class="mt-6 text-center">
                        <a href="{{ $posts->nextPageUrl() }}" class="btn btn-line w-full justify-center bg-page sm:w-auto" data-feed-more>
                            <x-heroicon-m-chevron-down class="size-5" aria-hidden="true" />{{ __('Show more posts') }}
                            <span class="font-mono text-sm text-muted">({{ number_format($posts->total() - $posts->lastItem()) }})</span>
                        </a>
                    </p>
                @endif
            @endif
        </div>

        {{-- Beside the feed, held in view as it is read: never taller than the screen (what does not fit scrolls inside it). --}}
        <aside class="feed-aside space-y-4">
            {{-- Looking for one in particular: the search of the list of announcements, here. --}}
            <form action="{{ route('announcements.index') }}" method="get" role="search" class="feed-panel">
                <h2 class="text-sm font-semibold">{{ __('Looking for one announcement?') }}</h2>
                @if ($department)
                    <input type="hidden" name="department" value="{{ $department->slug }}">
                @endif
                <label class="relative mt-2 block">
                    <span class="sr-only">{{ __('Word in the title') }}</span>
                    <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-muted" aria-hidden="true" />
                    <input type="search" name="q" maxlength="80" placeholder="{{ __('e.g. medicine, dental, interview') }}" class="min-h-11 w-full rounded-full border border-line bg-raised pl-10 pr-4 text-sm text-ink placeholder:text-muted">
                </label>
                <p class="mt-3 flex items-center justify-between gap-2 text-sm">
                    <a href="{{ route('announcements.index', array_filter(['department' => $department?->slug])) }}" class="font-semibold text-brand no-underline hover:underline">{{ __('All announcements') }} &rarr;</a>
                    <a href="{{ route('feed', array_filter(['department' => $department?->slug])) }}" class="inline-flex items-center gap-1 text-muted no-underline hover:text-brand"><x-heroicon-m-rss class="size-4" aria-hidden="true" />RSS</a>
                </p>
            </form>

            @if ($department)
                {{-- Who this is, with the way to its own page. --}}
                <section class="feed-panel flex items-center gap-3">
                    <x-site.department-avatar :department="$department" class="size-12 text-lg" />
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate font-semibold">{{ $department->localName() }}</h2>
                        @if (filled($department->localHeadline()))
                            <p class="line-clamp-2 text-sm text-muted">{{ $department->localHeadline() }}</p>
                        @endif
                        <a href="{{ route('departments.show', $department) }}" class="mt-1 inline-block text-sm font-semibold text-brand no-underline hover:underline">{{ __('See the profile') }} &rarr;</a>
                    </div>
                </section>
            @elseif ($mostRead->isNotEmpty())
                {{-- Not the departments again (they are in the row above): what readers have opened most. --}}
                <section class="feed-panel p-0" aria-labelledby="most-read-heading">
                    <h2 id="most-read-heading" class="flex items-center gap-2 px-4 pt-4 text-sm font-semibold"><x-heroicon-s-fire class="size-5 text-orange-500" aria-hidden="true" />{{ __('Most read') }}</h2>
                    <ol class="mt-1 pb-2">
                        @foreach ($mostRead as $one)
                            <li>
                                <a href="{{ route('announcements.show', $one) }}" class="group flex items-start gap-3 px-4 py-2 no-underline hover:bg-raised">
                                    <span class="mt-0.5 w-4 shrink-0 font-mono text-sm font-semibold text-muted" aria-hidden="true">{{ $loop->iteration }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="line-clamp-2 block text-sm leading-snug text-ink group-hover:text-brand">{{ $one->localTitle() }}</span>
                                        <span class="mt-0.5 block font-mono text-xs text-muted">{{ trans_choice('{1} opened :count time|[2,*] opened :count times', $one->views, ['count' => number_format($one->views)]) }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </aside>
    </div>
    </div>

    {{-- The window that opens from "Share" under a post. --}}
    <x-site.share-sheet />
</x-layouts.site>
