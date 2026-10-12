{{--
    Ways to pass a page on: to LINE, to Facebook, or its address copied (resources/js/share.js; without scripts
    the address is still there to copy by hand, in the address bar). Nothing is loaded from either network:
    these are plain links to their sharing pages, opened only when pressed.

    <x-site.share :url="route('announcements.show', $announcement)" :title="$announcement->localTitle()" />
--}}
@props(['url', 'title'])

<div {{ $attributes->class('flex flex-wrap items-center gap-2') }} aria-label="{{ __('Share') }}" role="group">
    <span class="text-sm text-muted">{{ __('Share') }}</span>
    <a href="https://social-plugins.line.me/lineit/share?url={{ urlencode($url) }}" target="_blank" rel="noopener noreferrer" class="chip min-h-9 px-3 text-sm">LINE<span class="sr-only"> {{ __('(opens in a new tab)') }}</span></a>
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($url) }}" target="_blank" rel="noopener noreferrer" class="chip min-h-9 px-3 text-sm">Facebook<span class="sr-only"> {{ __('(opens in a new tab)') }}</span></a>
    <button type="button" class="chip min-h-9 gap-1 px-3 text-sm" data-copy="{{ $url }}" data-copied="{{ __('Copied') }}" hidden>
        <x-heroicon-m-link class="size-4" aria-hidden="true" /><span data-copy-label>{{ __('Copy link') }}</span>
    </button>
</div>
