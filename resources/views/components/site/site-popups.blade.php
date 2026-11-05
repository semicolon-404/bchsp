{{--
    The popups set up on their own (App\Models\SitePopup), on the home page or every page. Each its own card: in the
    middle of the screen over the page, or in the lower corner, not covering it. Opened by resources/js/site-popup.js,
    one after another, each as often as it was set to (once, once a day, every visit), remembered in the visitor's
    browser. Staff signed in see one at once with ?popup=id ("Preview" in the panel).

    <x-site.site-popups :home="true" />
--}}
@props(['home' => false])
@php
    $preview = auth()->check() && is_numeric(request()->query('popup')) ? App\Models\SitePopup::find((int) request()->query('popup')) : null;
    $popups = $preview ? collect([$preview]) : App\Models\SitePopup::forPage($home);
@endphp

@foreach ($popups as $popup)
    <dialog class="site-popup is-{{ $popup->size }} is-{{ $popup->placement }}" tabindex="-1" data-site-popup="{{ $popup->seenKey() }}" data-frequency="{{ $preview ? 'always' : $popup->frequency }}" aria-labelledby="site-popup-{{ $popup->id }}" @if ($popup->accent) style="--accent: {{ $popup->accent }}" @endif>
        <form method="dialog" class="site-popup-x"><button aria-label="{{ __('Close') }}"><x-heroicon-m-x-mark class="size-5" aria-hidden="true" /></button></form>
        @if ($image = $popup->imageUrl())
            <img src="{{ $image }}" alt="" class="site-popup-picture" loading="lazy">
        @else
            <span class="site-popup-band" aria-hidden="true"></span>
        @endif
        <div class="site-popup-body">
            <h2 id="site-popup-{{ $popup->id }}" class="site-popup-title">{{ $popup->title }}</h2>
            @if (filled($popup->body))
                <div class="rich site-popup-text mt-2">{!! $popup->renderedBody() !!}</div>
            @endif
            <form method="dialog" class="site-popup-actions">
                @if ($popup->button_label && $popup->button_url)
                    <a href="{{ $popup->button_url }}" class="site-popup-main" @if (str_starts_with($popup->button_url, 'http')) target="_blank" rel="noopener" @endif>{{ $popup->button_label }}</a>
                @endif
                @if ($popup->second_label && $popup->second_url)
                    <a href="{{ $popup->second_url }}" class="btn btn-line" @if (str_starts_with($popup->second_url, 'http')) target="_blank" rel="noopener" @endif>{{ $popup->second_label }}</a>
                @endif
                <button class="btn btn-line">{{ __('Close') }}</button>
            </form>
        </div>
    </dialog>
@endforeach
