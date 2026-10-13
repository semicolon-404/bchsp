{{-- Under the panel's menu: which version of the site this is, leading to what changed (AdminPanelProvider). --}}
@if ($release = App\Models\SiteUpdate::newest())
    <p class="panel-version">
        <a href="{{ route('updates') }}" target="_blank" rel="noopener">v{{ $release->version }} &middot; {{ __('What is new') }}</a>
    </p>
@endif
