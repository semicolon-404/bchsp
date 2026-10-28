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
    // The colour of the day, as the site wears it (App\Support\Theme): today's, or the one colour where only one is set.
    $day = App\Support\Theme::touchOfTheDay() ?? App\Support\Theme::accent();
    $today = now()->locale('th');
@endphp

{{--
    The panel's sign-in page (App\Filament\Auth\Login): on a soft backdrop crossed by a pulse in the colour of the day,
    the hospital and the day in words on the left, and the form as a staff card hanging from its strap on the right.
    On a phone only the card shows, under the hospital's name. Styles: panel.css, "the sign-in page".
--}}
<x-filament-panels::layout.base :livewire="$livewire">
    {{-- Soft colours, blurred and drifting slowly; still for anyone who has asked for less motion. --}}
    <div class="signin-backdrop" style="--signin-day: {{ $day }}" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    <svg class="signin-pulse" style="--signin-day: {{ $day }}" viewBox="0 0 1200 120" preserveAspectRatio="none" fill="none" aria-hidden="true">
        <path d="M0 60H430l18-22 16 22h40l14-50 22 100 18-78 12 28h40l16-14 14 14H1200" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
    </svg>

    <div class="signin" style="--signin-day: {{ $day }}">
        <aside class="signin-words" aria-hidden="true">
            <div class="signin-brand">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" class="signin-logo">
                @endif
                <div>
                    <div class="signin-name">{{ $name }}</div>
                    <div class="signin-small">{{ __('Staff panel of the website') }}</div>
                </div>
            </div>

            <div x-data="{ now: new Date(), init() { setInterval(() => this.now = new Date(), 1000) } }">
                <div class="signin-today">{{ __('Today') }}</div>
                <div class="signin-weekday">{{ $today->translatedFormat('l') }}</div>
                <div class="signin-when">
                    <span>{{ $today->translatedFormat('j F ').$year }}</span>
                    <b x-text="now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', hour12: false })">{{ now()->format('H:i') }}</b>
                </div>
            </div>

            @if ($motto)
                <p class="signin-motto">“{{ $motto }}”</p>
            @else
                <span></span>
            @endif
        </aside>

        <div class="signin-side">
            <div class="signin-phone-brand">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" class="signin-logo">
                @endif
                <span>{{ $name }}</span>
            </div>

            <div class="signin-badge">
                <span class="signin-strap" aria-hidden="true"></span>
                <main id="fi-main-content" class="signin-card">
                    <div class="signin-card-label" aria-hidden="true"><span>{{ __('Staff card') }}</span><i></i></div>
                    {{ $slot }}
                </main>
            </div>

            <p class="signin-foot">{{ __('Every sign-in is recorded') }} · © {{ $year }} {{ $name }}</p>
        </div>
    </div>

    {{ FilamentView::renderHook(PanelsRenderHook::SIMPLE_LAYOUT_END, scopes: $renderHookScopes) }}
</x-filament-panels::layout.base>
