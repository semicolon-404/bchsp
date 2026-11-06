{{--
    The way to pass a page on: one Share button that opens the share window (components/site/share-sheet; LINE,
    Facebook, X, e-mail, a QR code, the phone's own sharing, the address to copy), and Print beside it where the
    browser can print. Nothing is loaded from any network until a button in the window is pressed.

    <x-site.share :url="$announcement->shortUrl()" :title="$announcement->localTitle()" />
--}}
@props(['url', 'title'])

<div {{ $attributes->class('flex flex-wrap items-center gap-2') }}>
    <button type="button" class="btn btn-line min-h-10 gap-2 px-4" data-share-url="{{ $url }}" data-share-title="{{ $title }}" data-shared="{{ __('Link copied') }}">
        <x-heroicon-m-share class="size-4" aria-hidden="true" /><span data-share-label>{{ __('Share') }}</span>
    </button>
    <button type="button" class="btn btn-line min-h-10 gap-2 px-4" data-print hidden>
        <x-heroicon-m-printer class="size-4" aria-hidden="true" />{{ __('Print') }}
    </button>
</div>
