{{--
    The popups set up on their own (App\Models\SitePopup), on the home page or every page. Those for the middle of the
    screen open together in one window, swiped or moved with the arrows (resources/js/slider.js), the size of the
    first; one ✕ closes them all. Each in the corner is a card of its own that does not cover the page. Each is shown
    as often as it was set to (resources/js/site-popup.js leaves out what this visitor has seen). Staff signed in see
    one at once with ?popup=id ("Preview" in the panel).

    <x-site.site-popups :home="true" />
--}}
@props(['home' => false])
@php
    $preview = auth()->check() && is_numeric(request()->query('popup')) ? App\Models\SitePopup::find((int) request()->query('popup')) : null;
    $popups = $preview ? collect([$preview]) : App\Models\SitePopup::forPage($home);
    [$corner, $middle] = $popups->partition(fn ($popup) => $popup->placement === 'corner');
    $many = $middle->count() > 1;
@endphp

@if ($middle->isNotEmpty())
    <dialog class="site-popup is-{{ $middle->first()->size }} is-center" tabindex="-1" data-site-popups aria-label="{{ $middle->first()->title }}">
        <form method="dialog" class="site-popup-x"><button aria-label="{{ __('Close') }}"><x-heroicon-m-x-mark class="size-5" aria-hidden="true" /></button></form>
        <div class="relative" @if ($many) data-slider data-slider-still aria-roledescription="carousel" @endif>
            <ul class="slider-track" data-slider-track>
                @foreach ($middle as $popup)
                    <li class="w-full shrink-0 snap-center" data-site-popup="{{ $popup->seenKey() }}" data-frequency="{{ $preview ? 'always' : $popup->frequency }}" @if ($popup->accent) style="--accent: {{ $popup->accent }}" @endif @if ($many) aria-roledescription="slide" @endif>
                        @include('components.site.site-popup-card', ['popup' => $popup])
                    </li>
                @endforeach
            </ul>
            @if ($many)
                <button type="button" class="slider-arrow popup-arrow left-3" data-slider-prev aria-label="{{ __('Previous slide') }}"><x-heroicon-m-chevron-left class="size-7" aria-hidden="true" /></button>
                <button type="button" class="slider-arrow popup-arrow right-3" data-slider-next aria-label="{{ __('Next slide') }}"><x-heroicon-m-chevron-right class="size-7" aria-hidden="true" /></button>
                <div class="popup-dots">
                    @foreach ($middle as $popup)
                        <button type="button" class="slider-dot" data-slider-dot aria-current="{{ $loop->first ? 'true' : 'false' }}" aria-label="{{ __('Slide :number', ['number' => $loop->iteration]) }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    </dialog>
@endif

@foreach ($corner as $popup)
    <dialog class="site-popup is-{{ $popup->size }} is-corner" tabindex="-1" data-site-popups aria-label="{{ $popup->title }}">
        <form method="dialog" class="site-popup-x"><button aria-label="{{ __('Close') }}"><x-heroicon-m-x-mark class="size-5" aria-hidden="true" /></button></form>
        <div data-site-popup="{{ $popup->seenKey() }}" data-frequency="{{ $preview ? 'always' : $popup->frequency }}" @if ($popup->accent) style="--accent: {{ $popup->accent }}" @endif>
            @include('components.site.site-popup-card', ['popup' => $popup])
        </div>
    </dialog>
@endforeach
