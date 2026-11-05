{{--
    The window that opens from "Share" under a post (components/site/post; resources/js/share.js), as a video site's:
    the post's title, round buttons to LINE, Facebook, X, e-mail, a QR code and the phone's own way of sharing, then the
    address with a button to copy it. Once on a page; the script fills it with the post pressed. Nothing is loaded
    from any of those networks: they are plain links to their sharing pages, opened only when pressed.

    <x-site.share-sheet />
--}}
<dialog class="share-sheet" tabindex="-1" data-share-sheet aria-labelledby="share-sheet-heading" data-qr="{{ route('qr') }}">
    <div class="flex items-center justify-between gap-4">
        <h2 id="share-sheet-heading" class="text-lg font-semibold">{{ __('Share') }}</h2>
        <form method="dialog"><button class="share-close" aria-label="{{ __('Close') }}"><x-heroicon-m-x-mark class="size-6" aria-hidden="true" /></button></form>
    </div>
    <p class="mt-1 line-clamp-2 text-sm text-muted" data-share-sheet-title></p>

    <ul class="share-targets mt-5">
        <li><a data-share-to="line" target="_blank" rel="noopener noreferrer"><span class="share-target-face" style="background: #06c755">LINE</span>LINE</a></li>
        <li><a data-share-to="facebook" target="_blank" rel="noopener noreferrer"><span class="share-target-face" style="background: #1877f2"><b class="text-2xl">f</b></span>Facebook</a></li>
        <li><a data-share-to="x" target="_blank" rel="noopener noreferrer"><span class="share-target-face" style="background: #000"><b class="text-xl">X</b></span>X</a></li>
        <li><a data-share-to="email"><span class="share-target-face" style="background: #64748b"><x-heroicon-o-envelope class="size-6" aria-hidden="true" /></span>{{ __('E-mail') }}</a></li>
        <li><a data-share-to="qr" target="_blank" rel="noopener"><span class="share-target-face" style="background: var(--color-brand)"><x-heroicon-o-qr-code class="size-6" aria-hidden="true" /></span>{{ __('QR code') }}</a></li>
        <li data-share-native hidden><button type="button"><span class="share-target-face" style="background: #475569"><x-heroicon-o-ellipsis-horizontal class="size-6" aria-hidden="true" /></span>{{ __('More') }}</button></li>
    </ul>

    <div class="share-link mt-5">
        <label class="sr-only" for="share-sheet-url">{{ __('Link') }}</label>
        <input id="share-sheet-url" type="text" readonly data-share-sheet-url>
        <button type="button" class="btn btn-brand rounded-full px-5" data-share-sheet-copy data-copied="{{ __('Copied') }}">{{ __('Copy') }}</button>
    </div>
</dialog>
