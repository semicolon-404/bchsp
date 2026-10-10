{{-- Light / dark / system, like the admin panel's. Handled by resources/js/theme.js. --}}
<div role="group" aria-label="{{ __('Theme') }}" {{ $attributes->class('switch grid-cols-3') }}>
    @foreach (['light' => ['Light theme', 'sun'], 'dark' => ['Dark theme', 'moon'], 'system' => ['Same as this device', 'computer-desktop']] as $mode => [$label, $icon])
        <button type="button" data-theme-mode="{{ $mode }}" aria-pressed="false" title="{{ __($label) }}">
            <x-dynamic-component :component="'heroicon-m-'.$icon" class="size-5" aria-hidden="true" />
            <span class="sr-only">{{ __($label) }}</span>
        </button>
    @endforeach
</div>
