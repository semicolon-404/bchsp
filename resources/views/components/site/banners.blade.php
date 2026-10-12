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

    // A picture of any shape, whole, in a frame of one shape: what the picture leaves empty is filled with the
    // same picture out of focus, so upright and wide ones sit side by side without gaps or cropping.
    $framed = function (Announcement $one, string $frame) {
        $inside = '<img src="'.e($one->imageUrl()).'" alt="" aria-hidden="true" loading="lazy" class="absolute inset-0 size-full scale-110 object-cover opacity-40 blur-2xl">'
            .'<img src="'.e($one->imageUrl()).'" alt="'.e($one->localTitle()).'" loading="lazy" class="relative size-full object-contain">';

        return '<a href="'.e(route('announcements.show', $one)).'" class="relative block overflow-hidden bg-raised '.$frame.'">'.$inside.'</a>';
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
                <p class="eyebrow">{{ __('Slides') }}</p>
                <h2 class="mt-2 text-2xl sm:text-3xl">{{ __('Banners') }}</h2>
            {{-- The arrows are placed against this box, so that they sit at the middle of the pictures and not of the heading. --}}
            <div class="mt-8"><x-site.banners part="stage" :banners="$banners" frame="aspect-[4/3] sm:aspect-[16/9] lg:aspect-[21/9]" /></div>
            </div>
        </section>
    @endif
@elseif ($part === 'stage')
    {{-- The slider alone: the pictures one at a time in a frame of one shape, with its arrows and dots. --}}
            <div class="relative" aria-roledescription="carousel" aria-label="{{ __('Banners') }}" data-slider>
                <ul class="slider-track rounded-xl border border-line" data-slider-track>
                    @foreach ($banners as $banner)
                        <li class="w-full shrink-0 snap-center" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}">{!! $framed($banner, $frame) !!}</li>
                    @endforeach
                </ul>

                @if ($banners->count() > 1)
                    <button type="button" class="slider-arrow left-2 sm:left-4" data-slider-prev aria-label="{{ __('Previous slide') }}"><x-heroicon-m-chevron-left class="size-6" aria-hidden="true" /></button>
                    <button type="button" class="slider-arrow right-2 sm:right-4" data-slider-next aria-label="{{ __('Next slide') }}"><x-heroicon-m-chevron-right class="size-6" aria-hidden="true" /></button>
                    <div class="absolute inset-x-0 bottom-2 flex justify-center gap-1">
                        @foreach ($banners as $banner)
                            <button type="button" class="slider-dot" data-slider-dot aria-current="{{ $loop->first ? 'true' : 'false' }}" aria-label="{{ __('Slide :number', ['number' => $loop->iteration]) }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
@elseif ($part === 'popup')
    @php($popup = Announcement::popup())
    @if ($popup)
        {{--
            Opened by resources/js/popup.js once the page has loaded, unless this visitor has closed this very
            poster before (remembered in their browser). A <dialog>: Escape closes it and the page behind is inert.
        --}}
        <dialog data-popup="{{ $popup->id }}" aria-label="{{ $popup->localTitle() }}" class="popup">
            <form method="dialog" class="flex justify-end p-2">
                <button class="btn btn-line min-h-10 px-3 py-1 text-sm" autofocus><x-heroicon-m-x-mark class="size-5" aria-hidden="true" />{{ __('Close') }}</button>
            </form>
            <a href="{{ route('announcements.show', $popup) }}" class="block"><img src="{{ $popup->imageUrl() }}" alt="{{ $popup->localTitle() }}" loading="lazy" class="mx-auto max-h-[calc(100vh-7rem)] w-auto max-w-full object-contain"></a>
        </dialog>
    @endif
@endif
