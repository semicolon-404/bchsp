{{--
    Phone and tablet navigation: a dock floating just above the bottom edge, where
    thumbs reach. Hidden from the lg breakpoint up, where the top bar has room for
    the same groups. Its tabs are the administrator's pick (App\Support\Menus); each
    leads to its group's first page, and "Menu" leads to the page that lists everything.

    The page keeps room for it at its foot (".has-dock" in app.css).
--}}
@php($tabs = App\Support\Menus::items('tabs'))

@if (count($tabs) > 1)
    <nav aria-label="{{ __('Main') }}" class="dock lg:hidden">
        <ul class="grid h-16 px-1" style="grid-template-columns: repeat({{ count($tabs) }}, minmax(0, 1fr))">
            @foreach ($tabs as $tab)
                <li>
                    <a href="{{ $tab['url'] }}" aria-current="{{ $tab['active'] ? 'page' : 'false' }}"
                       @class(['flex h-full flex-col items-center justify-center gap-0.5 text-xs no-underline', 'font-semibold text-brand' => $tab['active'], 'text-muted' => ! $tab['active']])>
                        <x-dynamic-component :component="'heroicon-'.($tab['active'] ? 's' : 'o').'-'.$tab['icon']" class="size-6" aria-hidden="true" />
                        <span class="max-w-full truncate px-0.5">{{ __($tab['short']) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
