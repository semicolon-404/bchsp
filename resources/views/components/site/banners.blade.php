{{--
    The pictures of announcements, where the site shows them for their own sake (App\Models\Announcement):
    the slides, and the one that opens over the page. Each leads to its announcement.

    <x-site.banners part="banners" />                      the slides as a section of the home page
    <x-site.banners part="stage" :banners="$slides" />     the slider alone, in a frame of the shape given (home page)
    <x-site.banners part="popup" />
--}}
@props(['part', 'banners' => null, 'frame' => 'aspect-[4/3]'])
@php
    use App\Models\Announcement;

    // A picture of any shape fills the frame, edge to edge: what does not fit is cut at the sides (or at the foot
    // of an upright poster, which keeps its heading at the top). The whole of it is on its announcement's page.
    $framed = function (Announcement $one, string $frame) {
        return '<a href="'.e(route('announcements.show', $one)).'" class="relative block overflow-hidden bg-raised '.$frame.'">'
            .'<img src="'.e($one->imageUrl()).'" alt="'.e($one->localTitle()).'" loading="lazy" class="size-full object-cover object-top">'
            .'</a>';
    };
@endphp

@if ($part === 'banners')
    @php($banners = Announcement::slides())
    @if ($banners->isNotEmpty())
        {{--
            The slides staff change most often, as a section of the home page. Each picture is shown
            whole in a frame of one size, whatever its shape, so the page does not jump from slide to slide.
            They can be swiped; resources/js/slider.js adds the arrows, the dots and moving on by itself.
        --}}
        <section class="bg-surface" aria-label="{{ __('Banners') }}">
            <div class="wrap py-12 sm:py-16">
                <h2 class="mt-2 text-2xl sm:text-3xl">{{ __('Banners') }}</h2>
            {{-- The arrows are placed against this box, so that they sit at the middle of the pictures and not of the heading. --}}
            <div class="mt-8"><x-site.banners part="stage" :banners="$banners" frame="aspect-[4/3] sm:aspect-[16/9] lg:aspect-[21/9]" /></div>
            </div>
        </section>
    @endif
@elseif ($part === 'stage')
    {{-- The slider alone: the pictures one at a time in a frame of one shape, with its arrows and dots. --}}
            <div class="relative" aria-roledescription="carousel" aria-label="{{ __('Banners') }}" data-slider>
                <ul class="slider-track poster-stage" data-slider-track>
                    @foreach ($banners as $banner)
                        {{-- The picture, and under it what it is about: its kind in its colour and its title, to be read without opening it. --}}
                        <li class="relative w-full shrink-0 snap-center" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}">
                            {!! $framed($banner, $frame) !!}
                            <a href="{{ route('announcements.show', $banner) }}" class="poster-caption" @if ($banner->type) style="--kind: {{ $banner->type->colour() }}" @endif aria-hidden="true" tabindex="-1">
                                @if ($banner->type)
                                    <span class="kind-pill">{{ $banner->type->localName() }}</span>
                                @endif
                                <span class="line-clamp-2 text-base font-semibold leading-[1.65] text-ink">{{ $banner->localTitle() }}</span>
                            </a>
                            {{-- An event with an album: its next photographs over the corner of the poster, and how many there are. --}}
                            @if ($photos = $banner->photos())
                                <a href="{{ route('announcements.show', $banner) }}" class="poster-album" aria-label="{{ trans_choice('{1} :count photograph|[2,*] :count photographs', count($photos), ['count' => count($photos)]) }}">
                                    @foreach (array_slice($photos, 0, 3) as $photo)
                                        <img src="{{ $photo }}" alt="" loading="lazy">
                                    @endforeach
                                    <span class="poster-album-count"><x-heroicon-s-photo class="size-4" aria-hidden="true" />{{ count($photos) }}</span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if ($banners->count() > 1)
                    <button type="button" class="slider-arrow left-2 sm:left-4" data-slider-prev aria-label="{{ __('Previous slide') }}"><x-heroicon-m-chevron-left class="size-6" aria-hidden="true" /></button>
                    <button type="button" class="slider-arrow right-2 sm:right-4" data-slider-next aria-label="{{ __('Next slide') }}"><x-heroicon-m-chevron-right class="size-6" aria-hidden="true" /></button>
                    <div class="slider-dots">
                        @foreach ($banners as $banner)
                            <button type="button" class="slider-dot" data-slider-dot aria-current="{{ $loop->first ? 'true' : 'false' }}" aria-label="{{ __('Slide :number', ['number' => $loop->iteration]) }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
@elseif ($part === 'popup')
    @php($popups = Announcement::popups())
    @if ($popups->isNotEmpty())
        {{--
            Opened by resources/js/popup.js once the page has loaded, unless this visitor has closed these very
            posters before (remembered in their browser). A <dialog>: Escape closes it and the page behind is inert.
            More than one: swiped, or moved through with the arrows (on screen or on the keyboard) and the dots.
        --}}
        <dialog autofocus tabindex="-1" data-popup="{{ $popups->pluck('id')->implode(',') }}" aria-label="{{ $popups->first()->localTitle() }}" class="popup">
            <form method="dialog" class="popup-close-x">
                <button aria-label="{{ __('Close') }}"><x-heroicon-m-x-mark class="size-6" aria-hidden="true" /></button>
            </form>
            <div class="relative" @if ($popups->count() > 1) data-slider data-slider-still aria-roledescription="carousel" @endif>
                <ul class="slider-track" data-slider-track>
                    @foreach ($popups as $popup)
                        <li class="w-full shrink-0 snap-center" @if ($loop->count > 1) aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}" @endif>
                            <a href="{{ route('announcements.show', $popup) }}" class="popup-picture"><img src="{{ $popup->imageUrl() }}" alt="{{ $popup->localTitle() }}" @unless ($loop->first) loading="lazy" @endunless></a>
                            <div class="popup-body">
                                <p class="flex flex-wrap items-center gap-2">
                                    @if ($popup->type)
                                        <span class="kind-pill" style="--kind: {{ $popup->type->colour() }}">{{ $popup->type->localName() }}</span>
                                    @endif
                                    @if ($loop->count > 1)
                                        <span class="text-sm text-muted">{{ $loop->iteration }} / {{ $loop->count }}</span>
                                    @endif
                                </p>
                                <h2 class="popup-title">{{ $popup->localTitle() }}</h2>
                                <form method="dialog" class="popup-actions">
                                    <a href="{{ route('announcements.show', $popup) }}" class="btn btn-brand popup-main">{{ __('Read the details') }} &rarr;</a>
                                    <button class="btn btn-line">{{ __('Close') }}</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($popups->count() > 1)
                    <button type="button" class="slider-arrow popup-arrow left-3" data-slider-prev aria-label="{{ __('Previous slide') }}"><x-heroicon-m-chevron-left class="size-7" aria-hidden="true" /></button>
                    <button type="button" class="slider-arrow popup-arrow right-3" data-slider-next aria-label="{{ __('Next slide') }}"><x-heroicon-m-chevron-right class="size-7" aria-hidden="true" /></button>
                    <div class="popup-dots">
                        @foreach ($popups as $popup)
                            <button type="button" class="slider-dot" data-slider-dot aria-current="{{ $loop->first ? 'true' : 'false' }}" aria-label="{{ __('Slide :number', ['number' => $loop->iteration]) }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        </dialog>
    @endif
@endif
