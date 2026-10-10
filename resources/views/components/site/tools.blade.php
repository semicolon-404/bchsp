{{--
    The thin bar above the header: the size of the words, and the light or dark look
    (resources/js/prefs.js). Each button says what it does in words, not by a symbol alone.
--}}
<div class="border-b border-line bg-surface text-sm">
    <div class="wrap flex flex-wrap items-center justify-end gap-x-4 gap-y-1 py-1">
        <div class="flex items-center gap-1" role="group" aria-label="{{ __('Text size') }}">
            <span class="mr-1 hidden text-muted sm:inline" aria-hidden="true">{{ __('Text size') }}</span>
            <button type="button" data-font-size="smaller" class="tool" aria-label="{{ __('Smaller text') }}">{{ __('A') }}<span aria-hidden="true">−</span></button>
            <button type="button" data-font-size="normal" class="tool" aria-label="{{ __('Normal text size') }}">{{ __('A') }}</button>
            <button type="button" data-font-size="larger" class="tool text-base" aria-label="{{ __('Larger text') }}">{{ __('A') }}<span aria-hidden="true">+</span></button>
        </div>

        <button type="button" data-theme-toggle aria-pressed="false" class="tool gap-2 px-3">
            <x-heroicon-o-moon class="size-5" aria-hidden="true" />
            {{ __('Dark mode') }}
        </button>
    </div>
</div>
