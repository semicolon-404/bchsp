{{--
    At the foot of a page of a group (About us, ITA): the page before it and the page after it, so a reader goes
    through the group without coming back to the list; on a phone a swipe does the same (resources/js/swipe.js).
    The same as under a page of text (page.blade.php), for the pages of the group that are drawn and not written.

    <x-site.page-neighbours :page="$page" />
--}}
@props(['page'])
@php([$before, $after] = $page->neighbours())

@if ($before || $after)
    <nav {{ $attributes->class('grid gap-3 border-t border-line pt-6 sm:grid-cols-2') }} aria-label="{{ __('Other pages of this group') }}"
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
