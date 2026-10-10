{{--
    What staff put on the home page for a while (App\Models\Banner): the wide banners across the top,
    the grid of posters, and the one poster that opens over the page.

    <x-site.banners part="banners" />   <x-site.banners part="posters" />   <x-site.banners part="popup" />
--}}
@props(['part'])
@php
    use App\Models\Banner;

    $picture = function (Banner $banner, string $class) {
        $image = '<img src="'.e($banner->imageUrl()).'" alt="'.e($banner->title).'" loading="lazy" class="'.$class.'">';

        return $banner->url
            ? '<a href="'.e($banner->url).'"'.($banner->leavesTheSite() ? ' target="_blank" rel="noopener noreferrer"' : '').' class="block">'.$image.'</a>'
            : $image;
    };

    // A picture of any shape, whole, in a frame of one shape: what the picture leaves empty is filled with the
    // same picture out of focus, so upright and wide ones sit side by side without gaps or cropping.
    $framed = function (Banner $banner, string $frame) {
        $inside = '<img src="'.e($banner->imageUrl()).'" alt="" aria-hidden="true" loading="lazy" class="absolute inset-0 size-full scale-110 object-cover opacity-40 blur-2xl">'
            .'<img src="'.e($banner->imageUrl()).'" alt="'.e($banner->title).'" loading="lazy" class="relative size-full object-contain">';
        // Without a link of its own it opens the picture itself, to be read at full size.
        $outside = $banner->url ? $banner->leavesTheSite() : true;

        return '<a href="'.e($banner->url ?: $banner->imageUrl()).'"'.($outside ? ' target="_blank" rel="noopener noreferrer"' : '').' class="relative block overflow-hidden bg-raised '.$frame.'">'.$inside.'</a>';
    };
@endphp

@if ($part === 'banners')
    @php($banners = Banner::slides())
    @if ($banners->isNotEmpty())
        {{--
            The slides staff change most often, as a section of the home page. Each picture is shown
            whole in a frame of one size, whatever its shape, so the page does not jump from slide to slide.
            They can be swiped; resources/js/slider.js adds the arrows, the dots and moving on by itself.
        --}}
        <section class="border-b border-line bg-surface" aria-label="{{ __('Banners') }}" aria-roledescription="carousel" data-slider>
            <div class="wrap py-12 sm:py-16">
                <p class="eyebrow">{{ __('Slides') }}</p>
                <h2 class="mt-2 text-2xl sm:text-3xl">{{ __('Banners') }}</h2>
            {{-- The arrows are placed against this box, so that they sit at the middle of the pictures and not of the heading. --}}
            <div class="relative mt-8">
                <ul class="slider-track rounded-xl border border-line" data-slider-track>
                    @foreach ($banners as $banner)
                        <li class="w-full shrink-0 snap-center" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}">{!! $framed($banner, 'aspect-[4/3] sm:aspect-[16/9] lg:aspect-[21/9]') !!}</li>
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
            </div>
        </section>
    @endif
@elseif ($part === 'posters')
    @php($posters = Banner::showingAs('poster'))
    @if ($posters->isNotEmpty())
        <section class="border-b border-line">
            <div class="wrap py-12 sm:py-16">
                <p class="eyebrow">{{ __('Posters') }}</p>
                <h2 class="mt-2 text-2xl sm:text-3xl">{{ __('Campaigns and notices') }}</h2>
                {{-- Every poster in a frame of the same upright shape, with its name under it: a tidy row whatever was uploaded. --}}
                <ul class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach ($posters as $poster)
                        <li class="card overflow-hidden p-0">
                            {!! $framed($poster, 'aspect-[3/4]') !!}
                            <p class="line-clamp-2 border-t border-line px-3 py-2 text-sm">{{ $poster->title }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
@elseif ($part === 'popup')
    @php($popup = Banner::popup())
    @if ($popup)
        {{--
            Opened by resources/js/popup.js once the page has loaded, unless this visitor has closed this very
            poster before (remembered in their browser). A <dialog>: Escape closes it and the page behind is inert.
        --}}
        <dialog data-popup="{{ $popup->id }}" aria-label="{{ $popup->title }}" class="popup">
            <form method="dialog" class="flex justify-end p-2">
                <button class="btn btn-line min-h-10 px-3 py-1 text-sm" autofocus><x-heroicon-m-x-mark class="size-5" aria-hidden="true" />{{ __('Close') }}</button>
            </form>
            {!! $picture($popup, 'mx-auto max-h-[calc(100vh-7rem)] w-auto max-w-full object-contain') !!}
        </dialog>
    @endif
@endif
