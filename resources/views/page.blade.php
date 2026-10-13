{{--
    /p/{slug}: a page of text written in the panel (App\Models\SitePage). Its title in the band at the top, its
    text in a column narrow enough to read, and, for a page filed in a group (About us, ITA), the other pages of
    the group beside it, with the one being read marked. It leads on to the page before it and the page after it,
    so a reader goes through the group without coming back to the list; on a phone a swipe does the same
    (resources/js/swipe.js).
--}}
@php
    [$before, $after] = $page->neighbours();
    $group = ['about' => ['About us', 'about'], 'ita' => ['ITA', 'ita']][$page->section] ?? null;
    $others = $group ? App\Models\SitePage::menuLinks($page->section) : collect();
    $place = $others->search(fn ($one) => $one->is($page));
@endphp
<x-layouts.site :title="$page->localTitle()" :description="$page->summary()">
    <x-site.page-header :eyebrow="$group ? __($group[0]) : null" :title="$page->localTitle()" icon="document-text" width="max-w-5xl">
        <p class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted">
            @if ($group)
                <a href="{{ route($group[1]) }}" class="font-semibold text-brand underline">&larr; {{ __($group[0]) }}</a>
            @endif
            @if ($place !== false && $others->count() > 1)
                <span class="font-mono">{{ $place + 1 }} / {{ $others->count() }}</span>
            @endif
            <x-site.updated :at="$page->updated_at" />
        </p>
    </x-site.page-header>

    <div @class(['wrap max-w-5xl py-10 sm:py-14', 'grid items-start gap-10 lg:grid-cols-4' => $others->count() > 1])>
        <article @class(['min-w-0', 'lg:col-span-3' => $others->count() > 1, 'mx-auto max-w-3xl' => $others->count() <= 1])>
            @unless ($page->is_published)
                {{-- Only an administrator gets this far (SitePageController). --}}
                <p class="mb-6 rounded-lg border border-danger px-4 py-3 text-danger" role="status">{{ __('Not published yet: only administrators can see this page.') }}</p>
            @endunless

            <div class="rich text-lg leading-relaxed">{!! $page->renderedBody() !!}</div>

            @if ($before || $after)
                <nav class="mt-12 grid gap-3 border-t border-line pt-6 sm:grid-cols-2" aria-label="{{ __('Other pages of this group') }}"
                     data-swipe @if ($before) data-swipe-prev="{{ route('pages.show', $before) }}" @endif @if ($after) data-swipe-next="{{ route('pages.show', $after) }}" @endif>
                    @if ($before)
                        <a href="{{ route('pages.show', $before) }}" rel="prev" class="card flex items-center gap-3 px-4 py-3 text-ink no-underline">
                            <x-heroicon-m-chevron-left class="size-5 shrink-0 text-brand" aria-hidden="true" />
                            <span class="min-w-0"><span class="block text-sm text-muted">{{ __('Previous') }}</span><span class="block">{{ $before->localTitle() }}</span></span>
                        </a>
                    @endif
                    @if ($after)
                        <a href="{{ route('pages.show', $after) }}" rel="next" class="card flex items-center justify-end gap-3 px-4 py-3 text-right text-ink no-underline sm:col-start-2">
                            <span class="min-w-0"><span class="block text-sm text-muted">{{ __('Next') }}</span><span class="block">{{ $after->localTitle() }}</span></span>
                            <x-heroicon-m-chevron-right class="size-5 shrink-0 text-brand" aria-hidden="true" />
                        </a>
                    @endif
                </nav>
            @endif
        </article>

        {{-- The other pages of the group, beside the text on a wide screen and under it on a phone; the one being read is marked. --}}
        @if ($others->count() > 1)
            <aside class="lg:sticky lg:top-24">
                <h2 class="eyebrow">{{ __($group[0]) }}</h2>
                <ul class="mt-3 space-y-1 border-l border-line">
                    @foreach ($others as $one)
                        <li>
                            <a href="{{ route('pages.show', $one) }}" @class(['-ml-px block border-l-2 py-2 pl-4 no-underline', 'border-brand font-semibold text-brand' => $one->is($page), 'border-transparent text-muted hover:text-ink' => ! $one->is($page)]) aria-current="{{ $one->is($page) ? 'page' : 'false' }}">{{ $one->localTitle() }}</a>
                        </li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>
</x-layouts.site>
