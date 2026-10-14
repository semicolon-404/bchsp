{{--
    What is behind the first screen of the home page, one thing after another (Site settings > The hospital):
    the site's own drawing with the welcome on it, then the photographs the hospital gave, each fading into the
    next. Each photograph says what is shown over it ("show"): the welcome and today's clinics as on the drawing,
    only its own line, or nothing, so the picture is seen whole. So the first screen is a showcase of what the
    hospital has to show and not one still picture.

    The pictures are decoration and say nothing a reader needs; the line under them is real text and a real link.
    resources/js/hero.js turns them: every few seconds by itself (never for a reader who asked for less motion),
    by the dots, and it stops while the bar is under the pointer or holds the focus, or when "pause" is pressed.
    The section they are in carries which is showing (data-kind, data-show), and the styles follow that.
    Without scripts the first stays, with its line.

    <x-site.hero-photos :slides="SiteSettings::heroSlides()" />     inside a .home-screen
--}}
@props(['slides'])

@foreach ($slides as $slide)
    @if ($slide['kind'] === 'art')
        <div @class(['hero-art', 'is-on' => $loop->first]) data-hero-slide data-kind="art" data-show="welcome" aria-hidden="true"><span></span><span></span><span></span></div>
    @else
        {{-- Only the first is fetched with the page; the others are fetched once the page has loaded (data-src). --}}
        <img @if ($loop->first) src="{{ $slide['url'] }}" fetchpriority="high" @else data-src="{{ $slide['url'] }}" @endif
             alt="" aria-hidden="true" @class(['hero-photo', 'is-on' => $loop->first]) data-hero-slide data-kind="photo" data-show="{{ $slide['show'] }}">
    @endif
@endforeach
<div class="hero-veil" aria-hidden="true"></div>

@if (count($slides) > 1 || filled($slides[0]['caption'] ?? null))
    <div class="hero-bar" data-hero-bar>
        <div class="wrap flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
            <p class="min-w-0" aria-live="polite">
                @foreach ($slides as $slide)
                    <span data-hero-caption @if (! $loop->first) hidden @endif>
                        @if (filled($slide['caption']) && $slide['show'] !== 'none')
                            @if ($slide['link'])
                                <a href="{{ $slide['link'] }}" class="hero-caption">{{ $slide['caption'] }}<x-heroicon-m-arrow-right class="size-4 shrink-0" aria-hidden="true" /></a>
                            @else
                                <span class="hero-caption">{{ $slide['caption'] }}</span>
                            @endif
                        @endif
                    </span>
                @endforeach
            </p>

            @if (count($slides) > 1)
                <div class="hero-dots" role="group" aria-label="{{ __('Photographs') }}">
                    @foreach ($slides as $slide)
                        <button type="button" data-hero-dot aria-label="{{ __('Photograph :number of :count', ['number' => $loop->iteration, 'count' => count($slides)]) }}" aria-current="{{ $loop->first ? 'true' : 'false' }}"></button>
                    @endforeach
                    <button type="button" class="hero-pause" data-hero-pause data-play="{{ __('Play') }}" data-stop="{{ __('Pause') }}" aria-label="{{ __('Pause') }}" hidden>
                        <x-heroicon-m-pause class="size-4" data-when="playing" aria-hidden="true" /><x-heroicon-m-play class="size-4" data-when="paused" aria-hidden="true" />
                    </button>
                </div>
            @endif
        </div>
    </div>
@endif
