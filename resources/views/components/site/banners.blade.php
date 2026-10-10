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
    @php($banners = Banner::showingAs('banner'))
    @if ($banners->isNotEmpty())
        {{-- With more than one, people swipe or scroll sideways between them; each snaps into place. --}}
        <section class="border-b border-line" aria-label="{{ __('Banners') }}">
            <div class="wrap py-6">
                <ul class="flex snap-x snap-mandatory gap-4 overflow-x-auto">
                    @foreach ($banners as $banner)
                        <li class="w-full shrink-0 snap-center overflow-hidden rounded-xl border border-line">{!! $picture($banner, 'aspect-[16/5] w-full object-cover') !!}</li>
                    @endforeach
                </ul>
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
            {!! $picture($popup, 'max-h-[75vh] w-full object-contain') !!}
        </dialog>
    @endif
@endif
