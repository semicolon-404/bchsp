{{-- Beside the person's menu in the panel: the way to the site itself, in a new tab (AdminPanelProvider). --}}
<a href="{{ url('/') }}" target="_blank" rel="noopener" class="panel-site-link" title="{{ __('Open the website') }}">
    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" /><span>{{ __('Open the website') }}</span>
</a>
