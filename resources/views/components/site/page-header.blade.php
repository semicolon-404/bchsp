{{--
    The band at the top of a page: what the page is, said large, on the soft light of the site's colour. Every
    page of the site opens with one, so a reader always knows where they are before reading on.

    <x-site.page-header :eyebrow="__('Downloads')" :title="__('Forms and documents')" :lead="__('...')" icon="arrow-down-tray">
        (what belongs with the heading: a search box, a row of figures, a row of choices)
    </x-site.page-header>

    icon: a heroicon (outline) set in a tile beside the heading on screens with room for it.
    width: the same max-width class as the content under it, so the heading lines up with it.
--}}
@props(['title', 'eyebrow' => null, 'lead' => null, 'icon' => null, 'width' => ''])

<section class="home-screen !min-h-0 border-b border-line">
    <div class="wrap {{ $width }} py-10 sm:py-14">
        <div class="flex items-start gap-5">
            @if ($icon)
                <span class="hidden size-16 shrink-0 items-center justify-center rounded-2xl bg-brand text-on-brand shadow-lg sm:flex" aria-hidden="true">
                    <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-8" />
                </span>
            @endif
            <div class="min-w-0 flex-1">
                @if ($eyebrow)
                    <p class="eyebrow">{{ $eyebrow }}</p>
                @endif
                <h1 class="hero-name mt-2 text-3xl sm:text-5xl">{{ $title }}</h1>
                @if ($lead)
                    <p class="mt-3 max-w-2xl text-lg text-muted">{{ $lead }}</p>
                @endif
            </div>
        </div>
        @if ($slot->isNotEmpty())
            <div class="mt-6">{{ $slot }}</div>
        @endif
    </div>
</section>
