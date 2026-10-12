{{--
    /p/{slug}: a page of text written in the panel (App\Models\SitePage). One filed in a group (About us, ITA)
    leads on to the page before it and the page after it, so a reader goes through the group without coming back
    to the list; on a phone a swipe does the same (resources/js/swipe.js).
--}}
@php
    [$before, $after] = $page->neighbours();
    $group = ['about' => ['About us', 'about'], 'ita' => ['ITA', 'ita']][$page->section] ?? null;
@endphp
<x-layouts.site :title="$page->localTitle()" :description="$page->summary()">
    <article class="wrap max-w-3xl py-10 sm:py-14">
        @unless ($page->is_published)
            {{-- Only an administrator gets this far (SitePageController). --}}
            <p class="mb-6 rounded-lg border border-danger px-4 py-3 text-danger" role="status">{{ __('Not published yet: only administrators can see this page.') }}</p>
        @endunless

        @if ($group)
            <p class="mb-6 text-sm"><a href="{{ route($group[1]) }}" class="font-semibold text-brand underline">&larr; {{ __($group[0]) }}</a></p>
        @endif

        <h1 class="text-3xl sm:text-4xl">{{ $page->localTitle() }}</h1>
        <div class="rich mt-8">{!! $page->renderedBody() !!}</div>
        <x-site.updated :at="$page->updated_at" class="mt-10" />

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
</x-layouts.site>
