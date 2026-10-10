{{--
    The top bar's menu, in groups (App\Support\Menus; System > Site settings > "Menus").
    A group with something under it opens a list when pressed; resources/js/menu.js closes it again
    on a press elsewhere or on Escape. Without scripts it still opens and closes: it is a <details>.
    On phones and tablets the dock at the bottom is used instead (components/site/mobile-tabs).
--}}
<nav class="hidden items-stretch gap-1 self-stretch lg:flex" aria-label="{{ __('Main') }}">
    @foreach (App\Support\Menus::items('top') as $item)
        @php($classes = 'flex h-full items-center gap-1 whitespace-nowrap px-3 text-sm no-underline transition-colors hover:text-ink '.($item['active'] ? 'text-ink shadow-[inset_0_-2px_0_var(--color-brand)]' : 'text-muted'))

        @if ($item['opens'])
            <details class="menu-group relative" data-menu>
                <summary class="{{ $classes }} cursor-pointer list-none">
                    {{ __($item['label']) }}
                    <x-heroicon-m-chevron-down class="size-4" aria-hidden="true" />
                </summary>
                <ul class="menu-list">
                    @foreach ($item['children'] as $child)
                        <li><a href="{{ $child['url'] }}" aria-current="{{ $child['active'] ? 'page' : 'false' }}">{{ $child['label'] }}</a></li>
                    @endforeach
                </ul>
            </details>
        @else
            <a href="{{ $item['url'] }}" aria-current="{{ $item['active'] ? 'page' : 'false' }}" class="{{ $classes }}">{{ __($item['label']) }}</a>
        @endif
    @endforeach
</nav>
