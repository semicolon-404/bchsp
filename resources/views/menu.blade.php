{{--
    /menu: everything on the site, group by group. The dock's "Menu" tab leads here, so a phone reaches
    what its dock has no room for; it also serves anyone who would rather read a list than open menus.
--}}
<x-layouts.site :title="__('Menu')">
    <div class="wrap max-w-4xl py-10 sm:py-14">
        <p class="eyebrow">{{ __('Menu') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('Everything on this website') }}</h1>

        <div class="mt-8 grid gap-6 sm:grid-cols-2">
            @foreach (App\Support\Menus::items('all') as $item)
                <section class="card">
                    <h2 class="text-lg"><a href="{{ $item['url'] }}" class="flex items-center gap-2 text-ink no-underline hover:text-brand">
                        <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="size-5 text-brand" aria-hidden="true" />{{ __($item['label']) }}</a></h2>
                    @if ($item['children'])
                        <ul class="mt-3 space-y-1 border-l border-line pl-4">
                            @foreach ($item['children'] as $child)
                                <li><a href="{{ $child['url'] }}" class="block py-1.5 text-muted no-underline hover:text-brand">{{ $child['label'] }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach

            <section class="card">
                <h2 class="text-lg"><a href="{{ route('staff') }}" class="flex items-center gap-2 text-ink no-underline hover:text-brand">
                    <x-heroicon-o-identification class="size-5 text-brand" aria-hidden="true" />{{ __('For staff') }}</a></h2>
                <p class="mt-3 text-sm text-muted">{{ __('Services for people who work at the hospital.') }}</p>
            </section>
        </div>

        <div class="mt-8 flex flex-wrap items-center gap-3">
            <x-site.language-switch />
            <x-site.theme-switch />
        </div>
    </div>
</x-layouts.site>
