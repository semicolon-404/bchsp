{{--
    The top bar's menu, in groups (App\Support\Menus; System > Site settings > "Menus").
    A group with something under it opens a list when pressed; resources/js/menu.js closes it again
    on a press elsewhere or on Escape. Without scripts it still opens and closes: it is a <details>.
    It sits at the right of the bar, beside the search and the settings; the name keeps the left to itself.
    On phones and tablets the dock at the bottom is used instead (components/site/mobile-tabs).
--}}
<nav class="ml-auto hidden items-stretch gap-1 self-stretch lg:flex" aria-label="{{ __('Main') }}">
    @foreach (App\Support\Menus::items('top') as $item)
        @php($classes = 'flex h-full items-center gap-1 whitespace-nowrap px-3 text-sm no-underline transition-colors hover:text-ink '.($item['active'] ? 'text-ink shadow-[inset_0_-2px_0_var(--color-brand)]' : 'text-muted'))

        @if ($item['opens'])
            <details class="menu-group relative" data-menu>
                {{-- A double press goes straight to the group's own page (resources/js/menu.js), as the first line of the list does. --}}
                <summary class="{{ $classes }} cursor-pointer list-none" data-menu-url="{{ $item['url'] }}" title="{{ __('Double-click to see all') }}">
                    {{ __($item['label']) }}
                    <x-heroicon-m-chevron-down class="size-4" aria-hidden="true" />
                </summary>
                <ul class="menu-list">
                    {{-- The way to all of it: a small button in the list's top corner. --}}
                    <li class="menu-all-spot"><a href="{{ $item['url'] }}" class="menu-all" title="{{ __('Open the whole section') }}"><x-heroicon-m-arrow-up-right class="size-4" aria-hidden="true" /><span class="sr-only">{{ __('Open the whole section') }}</span></a></li>
                    @foreach ($item['children'] as $child)
                        <li><a href="{{ $child['url'] }}" aria-current="{{ $child['active'] ? 'page' : 'false' }}">{{ $child['label'] }}</a></li>
                    @endforeach
                </ul>
            </details>
        @else
            {{-- A link of the administrator's own is shown as they named it, and opens in a new tab when it leaves the site. --}}
            <a href="{{ $item['url'] }}" aria-current="{{ $item['active'] ? 'page' : 'false' }}" class="{{ $classes }}"
               @if (($item['own'] ?? false) && parse_url($item['url'], PHP_URL_HOST) !== request()->getHost()) target="_blank" rel="noopener noreferrer" @endif>{{ ($item['own'] ?? false) ? $item['label'] : __($item['label']) }}</a>
        @endif
    @endforeach
</nav>
