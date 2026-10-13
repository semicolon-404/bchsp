{{--
    /menu: everything on the site, group by group. The dock's "Menu" tab leads here, so a phone reaches
    what its dock has no room for; it also serves anyone who would rather read a list than open menus.
--}}
<x-layouts.site :title="__('Menu')">
    <x-site.page-header :eyebrow="__('Menu')" :title="__('Everything on this website')" icon="bars-3" width="max-w-5xl"
                        :lead="__('Every part of the site on one page. Press a heading, or any line under it.')">
        <form action="{{ route('search') }}" method="get" role="search" class="flex max-w-md gap-2">
            <label class="relative block min-w-0 flex-1">
                <span class="sr-only">{{ __('Words to look for') }}</span>
                <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-muted" aria-hidden="true" />
                <input type="search" name="q" maxlength="80" placeholder="{{ __('Search this website') }}" class="min-h-12 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-ink placeholder:text-muted">
            </label>
            <button class="btn btn-brand">{{ __('Search') }}</button>
        </form>
    </x-site.page-header>

    <div class="wrap max-w-5xl py-10 sm:py-14">
        <div class="grid items-start gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (App\Support\Menus::items('all') as $item)
                <section class="card">
                    <h2 class="text-lg"><a href="{{ $item['url'] }}" class="group flex items-center gap-3 text-ink no-underline hover:text-brand">
                        <span class="door-mark" aria-hidden="true"><x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="size-5" /></span>{{ ($item['own'] ?? false) ? $item['label'] : __($item['label']) }}</a></h2>
                    @if ($item['children'])
                        <ul class="mt-4 space-y-0.5 border-l border-line pl-4">
                            @foreach ($item['children'] as $child)
                                <li><a href="{{ $child['url'] }}" class="flex items-center justify-between gap-2 py-2 text-muted no-underline hover:text-brand">{{ $child['label'] }}<x-heroicon-m-chevron-right class="size-4 shrink-0 opacity-50" aria-hidden="true" /></a></li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach

            <section class="card">
                <h2 class="text-lg"><a href="{{ route('staff') }}" class="group flex items-center gap-3 text-ink no-underline hover:text-brand">
                    <span class="door-mark" aria-hidden="true"><x-heroicon-o-identification class="size-5" /></span>{{ __('For staff') }}</a></h2>
                <p class="mt-3 text-sm text-muted">{{ __('Services for people who work at the hospital.') }}</p>
            </section>
        </div>

        <div class="card mt-8 flex flex-wrap items-center gap-x-6 gap-y-3">
            <p class="font-medium">{{ __('Language and display') }}</p>
            <x-site.language-switch />
            <x-site.theme-switch />
        </div>
    </div>
</x-layouts.site>
