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
@endphp

@if ($part === 'banners')
    @php($banners = Banner::slides())
    @if ($banners->isNotEmpty())
        {{--
            The slides staff change most often, so they are the first thing on the page. Each picture is shown
            whole, whatever its shape. They can be swiped; resources/js/slider.js adds the arrows, the dots and
            moving on by itself.
        --}}
        <section class="border-b border-line bg-surface" aria-label="{{ __('Banners') }}" aria-roledescription="carousel" data-slider>
            <div class="wrap relative py-4 sm:py-6">
                <ul class="slider-track" data-slider-track>
                    @foreach ($banners as $banner)
                        <li class="flex w-full shrink-0 snap-center items-center justify-center" aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}">{!! $picture($banner, 'mx-auto max-h-[70vh] w-auto max-w-full rounded-xl object-contain') !!}</li>
                    @endforeach
                </ul>

                @if ($banners->count() > 1)
                    <button type="button" class="slider-arrow left-2 sm:left-8" data-slider-prev aria-label="{{ __('Previous slide') }}"><x-heroicon-m-chevron-left class="size-6" aria-hidden="true" /></button>
                    <button type="button" class="slider-arrow right-2 sm:right-8" data-slider-next aria-label="{{ __('Next slide') }}"><x-heroicon-m-chevron-right class="size-6" aria-hidden="true" /></button>
                    <div class="mt-3 flex justify-center gap-2">
                        @foreach ($banners as $banner)
                            <button type="button" class="slider-dot" data-slider-dot aria-current="{{ $loop->first ? 'true' : 'false' }}" aria-label="{{ __('Slide :number', ['number' => $loop->iteration]) }}"></button>
                        @endforeach
                    </div>
                @endif
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
                <ul class="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach ($posters as $poster)
                        <li class="overflow-hidden rounded-xl border border-line bg-surface">{!! $picture($poster, 'w-full') !!}</li>
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
