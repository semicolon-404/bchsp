{{--
    The links of the top bar, as an administrator arranged them (App\Support\Menus; System > Site settings > "Menus").
    On phones and tablets these live in the dock at the bottom instead (components/site/mobile-tabs).
--}}
<nav class="hidden items-stretch gap-1 self-stretch lg:flex" aria-label="{{ __('Main') }}">
    @foreach (App\Support\Menus::items('top') as $link)
        {{-- "blog.*" also lights up on an article, and so on. --}}
        @php($active = request()->routeIs($link['pattern']))
        {{-- The current page is marked by a brand-coloured bar sitting on the header's bottom edge. --}}
        <a href="{{ route($link['route']) }}" aria-current="{{ $active ? 'page' : 'false' }}"
           @class([
               // nowrap: a label of two words stays on one line however full the bar is.
               'flex items-center whitespace-nowrap px-3 font-medium no-underline transition-colors hover:text-ink',
               'text-ink shadow-[inset_0_-2px_0_var(--color-brand)]' => $active,
               'text-muted' => ! $active,
           ])>
            {{ __($link['label']) }}
        </a>
    @endforeach
</nav>
