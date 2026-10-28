@php
    use App\Support\SiteSettings;
    use Filament\Support\Facades\FilamentView;
    use Filament\View\PanelsRenderHook;

    $livewire ??= null;
    $renderHookScopes = $livewire?->getRenderHookScopes();
    $name = SiteSettings::name();
    $logo = SiteSettings::logoUrl();
    $motto = SiteSettings::get('site.motto');
    $year = now()->year + 543;
@endphp

{{--
    The panel's sign-in page in two halves (App\Filament\Auth\Login): the hospital and the time on the left, the
    form on the right. On a phone only the form shows, under the hospital's name. Styles: panel.css, "the sign-in page".
--}}
<x-filament-panels::layout.base :livewire="$livewire">
    {{-- Soft colours, blurred and drifting slowly, under frosted glass; still for anyone who has asked for less motion. --}}
    <div class="signin-backdrop" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    <div class="signin">
        <aside class="signin-visual" aria-hidden="true">
            <div class="signin-visual-top">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" class="signin-logo">
                @endif
                <div>
                    <div class="signin-name">{{ $name }}</div>
                    <div class="signin-small">{{ __('Staff panel of the website') }}</div>
                </div>
            </div>

            <div x-data="{ now: new Date(), init() { setInterval(() => this.now = new Date(), 1000) } }">
                <div class="signin-label">{{ __('The time now') }}</div>
                <div class="signin-clock" x-text="now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', hour12: false })">{{ now()->format('H:i') }}</div>
                <div class="signin-date">{{ now()->locale('th')->translatedFormat('l j F ').$year }}</div>
            </div>

            <div class="signin-steps">
                <div class="signin-small">{{ __('How a post reaches the website') }}</div>
                <ol>
                    <li><span>1</span>{{ __('A department writes it') }}</li>
                    <li><span>2</span>{{ __('An administrator checks it') }}</li>
                    <li><span>3</span>{{ __('It is out on the website') }}</li>
                </ol>
                @if ($motto)
                    <p class="signin-motto">“{{ $motto }}”</p>
                @endif
            </div>
        </aside>

        <div class="signin-side">
            <div class="signin-phone-brand">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" class="signin-logo">
                @endif
                <span>{{ $name }}</span>
            </div>

            <main id="fi-main-content" class="signin-card">
                {{ $slot }}
            </main>

            <p class="signin-foot">{{ __('Every sign-in is recorded') }} · © {{ $year }} {{ $name }}</p>
        </div>
    </div>

    {{ FilamentView::renderHook(PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
</x-filament-panels::layout.base>
