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
    @if ($wall->count() >= 3 && ! $department)
        {{-- A wall of what the departments have put up lately, rows drifting past each other behind the title: the
             feed's own pictures, changing as they post. Still for those who ask for less motion. --}}
        @php($rows = [$wall->values(), $wall->reverse()->values(), $wall->shuffle(7)->values()])
        <section class="wall" aria-labelledby="wall-heading">
            <div class="wall-rows" aria-hidden="true">
                @foreach ($rows as $row)
                    <div class="wall-row {{ $loop->odd ? '' : 'is-back' }}">
                        @foreach ([$row, $row] as $copy)
                            @foreach ($copy as $one)
                                <a href="{{ route('announcements.show', $one) }}" tabindex="-1" class="wall-tile">
                                    <img src="{{ $one->imageUrl() }}" alt="" @if ($loop->parent->index > 0 || $loop->parent->parent->index > 0) loading="lazy" @endif>
                                </a>
                            @endforeach
                        @endforeach
                    </div>
                @endforeach
            </div>
            <div class="wall-shade" aria-hidden="true"></div>
            <div class="wrap relative max-w-5xl py-16 sm:py-24">
                <p class="eyebrow text-white/80">{{ __('Feed') }}</p>
                <h1 id="wall-heading" class="mt-2 text-4xl font-semibold leading-tight text-white sm:text-6xl">{{ __('From the departments') }}</h1>
                <p class="mt-3 max-w-xl text-lg text-white/85">{{ __('What the hospital\'s departments are doing and saying, newest first.') }}</p>
                <p class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-white">
                    <span class="inline-flex items-center gap-2"><span class="relative flex size-2.5" aria-hidden="true"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-300 opacity-70 motion-reduce:hidden"></span><span class="relative inline-flex size-2.5 rounded-full bg-emerald-400"></span></span>{{ __('Last post') }} {{ $wall->first()->shownAt() }}</span>
                    <span><b class="font-mono text-xl" data-count>{{ number_format($posts->total()) }}</b> {{ __('posts') }}</span>
                    <span><b class="font-mono text-xl" data-count>{{ number_format($departments->count()) }}</b> {{ __('Departments that posted') }}</span>
                </p>
            </div>
        </section>
    @else
        <x-site.page-header :eyebrow="__('Feed')" :title="$department ? $department->localName() : __('From the departments')" icon="newspaper"
                            :lead="__('What the hospital\'s departments are doing and saying, newest first.')">
            <dl class="flex flex-wrap gap-x-10 gap-y-4">
                <div>
                    <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($posts->total()) }}</dd>
                    <dt class="text-sm text-muted">{{ __('posts') }}</dt>
                </div>
                @unless ($department)
                    <div>
                        <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($departments->count()) }}</dd>
                        <dt class="text-sm text-muted">{{ __('Departments that posted') }}</dt>
                    </div>
                @endunless
            </dl>
        </x-site.page-header>
    @endif

    @if ($departments->isNotEmpty())
        {{-- The departments that have posted, as round pictures to press, as stories are: a ring round those that posted
             this week. The row stays in reach as the feed is read, and slides sideways on a phone. --}}
        <nav class="sticky top-16 z-30 border-b border-line bg-page/90 backdrop-blur" aria-label="{{ __('Departments') }}">
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
                <ul class="space-y-4 sm:space-y-5">
                    @foreach ($posts as $announcement)
                        <x-site.post :announcement="$announcement" :department="$announcement->department" linked />
                    @endforeach
                </ul>

                <div class="mt-10">{{ $posts->links('components.site.pagination') }}</div>
            @endif
        </div>

        <aside class="space-y-8 lg:sticky lg:top-36">
            @if ($department)
                {{-- Who this is, with the way to its own page. --}}
                <section class="card text-center">
                    <x-site.department-avatar :department="$department" class="mx-auto size-20 text-3xl" />
                    <h2 class="mt-3 text-lg">{{ $department->localName() }}</h2>
                    @if (filled($department->localHeadline()))
                        <p class="mt-1 text-sm text-muted">{{ $department->localHeadline() }}</p>
                    @endif
                    <a href="{{ route('departments.show', $department) }}" class="btn btn-brand mt-4">{{ __('See the profile') }} &rarr;</a>
                </section>
            @elseif ($mostRead->isNotEmpty())
                {{-- Not the departments again (they are in the row above): what readers have opened most. --}}
                <section aria-labelledby="most-read-heading">
                    <h2 id="most-read-heading" class="flex items-center gap-2 text-xl"><x-heroicon-o-fire class="size-5 text-brand" aria-hidden="true" />{{ __('Most read') }}</h2>
                    <ol class="card mt-3 p-0">
                        @foreach ($mostRead as $one)
                            <li class="border-b border-line last:border-b-0">
                                <a href="{{ route('announcements.show', $one) }}" class="group flex items-start gap-3 px-4 py-3 no-underline">
                                    <span class="w-5 shrink-0 text-center font-mono text-lg font-semibold text-brand" aria-hidden="true">{{ $loop->iteration }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="line-clamp-2 block text-ink group-hover:text-brand">{{ $one->localTitle() }}</span>
                                        <span class="mt-0.5 block font-mono text-xs text-muted">{{ trans_choice('{1} opened :count time|[2,*] opened :count times', $one->views, ['count' => number_format($one->views)]) }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            <section class="card">
                <h2 class="text-lg">{{ __('Looking for one announcement?') }}</h2>
                <p class="mt-1 text-sm text-muted">{{ __('The list of announcements can be searched and narrowed by kind and by date.') }}</p>
                <p class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('announcements.index', array_filter(['department' => $department?->slug])) }}" class="btn btn-line">{{ __('All announcements') }} &rarr;</a>
                    <a href="{{ route('feed', array_filter(['department' => $department?->slug])) }}" class="btn btn-line"><x-heroicon-m-rss class="size-4" aria-hidden="true" />RSS</a>
                </p>
            </section>
        </aside>
    </div>
    </div>

    {{-- The window that opens from "Share" under a post. --}}
    <x-site.share-sheet />
</x-layouts.site>
