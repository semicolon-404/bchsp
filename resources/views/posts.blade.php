{{--
    /posts: the feed. What the departments have posted, newest first, each as a post with the department's picture
    and name over it, as on a department's own page but all of them together (AnnouncementController::posts).
    A row of the departments narrows it to one (?department=...); beside it, who posts and how much.

    The list of announcements (/announcements) is for finding one; this is for reading down, to see what the
    hospital's people are doing.
--}}
@php
    $with = fn (?string $slug) => route('posts', array_filter(['department' => $slug]));
@endphp
<x-layouts.site :title="$department ? __('Posts from :name', ['name' => $department->localName()]) : __('Feed')" :description="__('What the departments of :name have posted, newest first.', ['name' => App\Support\SiteSettings::name()])">
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

    @if ($departments->isNotEmpty())
        {{-- The departments that have posted, one press away; the row stays in reach as the feed is read. --}}
        <nav class="sticky top-16 z-30 border-b border-line bg-page/90 backdrop-blur" aria-label="{{ __('Departments') }}">
            <ul class="wrap flex max-w-5xl gap-2 overflow-x-auto py-3 [scrollbar-width:none]">
                <li class="shrink-0"><a href="{{ $with(null) }}" @class(['chip whitespace-nowrap', 'is-on' => ! $department]) aria-current="{{ $department ? 'false' : 'page' }}">{{ __('All') }}</a></li>
                @foreach ($departments as $one)
                    <li class="shrink-0">
                        <a href="{{ $with($one->slug) }}" @class(['chip gap-2 whitespace-nowrap py-1 pl-1 pr-4', 'is-on' => $department?->is($one)]) aria-current="{{ $department?->is($one) ? 'page' : 'false' }}">
                            <x-site.department-avatar :department="$one" class="size-8 text-sm" />{{ $one->localName() }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    <div class="wrap grid max-w-5xl items-start gap-10 py-10 sm:py-14 lg:grid-cols-3">
        <div class="min-w-0 lg:col-span-2">
            @if ($posts->isEmpty())
                <x-site.empty icon="newspaper">{{ __('No department has posted anything yet.') }}</x-site.empty>
            @else
                <ul class="space-y-5">
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
            @elseif ($departments->isNotEmpty())
                <section aria-labelledby="who-heading">
                    <h2 id="who-heading" class="flex items-center gap-2 text-xl"><x-heroicon-o-building-office-2 class="size-5 text-brand" aria-hidden="true" />{{ __('Who is posting') }}</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($departments as $one)
                            <li>
                                <a href="{{ $with($one->slug) }}" class="card flex items-center gap-3 px-3 py-2 text-ink no-underline">
                                    <x-site.department-avatar :department="$one" class="size-9 text-sm" />
                                    <span class="min-w-0 flex-1 truncate">{{ $one->localName() }}</span>
                                    <span class="shrink-0 font-mono text-sm text-muted">{{ number_format($one->posted) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
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
</x-layouts.site>
