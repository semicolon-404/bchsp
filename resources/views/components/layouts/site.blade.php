{{--
    The frame of every public page: the bar at the top, the page itself, the footer.

    <x-layouts.site title="Contact" description="How to reach us">...</x-layouts.site>

    Without a title the page is named after the hospital alone.
--}}
@props(['title' => null, 'description' => null, 'noindex' => false])
@php
    use App\Support\SiteSettings;

    $name = SiteSettings::name();
    $logo = SiteSettings::logoUrl();
    $phone = SiteSettings::get('contact.phone');
    $emergency = SiteSettings::get('contact.emergency_phone');
    $address = SiteSettings::get('contact.address');
    $email = SiteSettings::get('contact.email');
    $social = SiteSettings::socialLinks();
    $pages = App\Models\SitePage::footerLinks();
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['is-mourning' => App\Support\Theme::isMourning()])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · '.$name : $name }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @if ($noindex)
        <meta name="robots" content="noindex">
    @endif
    {{-- Runs before the first paint so a light-mode visitor never sees a dark flash. Same storage key as the admin panel; see resources/js/theme.js. --}}
    <script>
        (function () {
            var mode = 'dark';
            try { mode = localStorage.getItem('theme') || 'dark'; } catch (e) {}
            var light = mode === 'light' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: light)').matches);
            document.documentElement.dataset.theme = light ? 'light' : 'dark';
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- The colour an administrator gave the site, laid over the stylesheet's own green (App\Support\Theme). --}}
    @if ($themeCss = App\Support\Theme::css())
        <style>{!! $themeCss !!}</style>
    @endif
</head>
<body class="has-dock flex min-h-screen flex-col font-sans antialiased">
    <a href="#main" class="btn btn-brand sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50">{{ __('Skip to the content') }}</a>

    <header class="sticky top-0 z-40 border-b border-line bg-page/90 backdrop-blur">
        <div class="wrap flex min-h-16 items-center gap-3 py-2">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 font-semibold text-ink no-underline">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" class="h-8 w-auto shrink-0">
                @else
                    <span class="flex size-8 shrink-0 items-center justify-center rounded bg-brand text-on-brand" aria-hidden="true">
                        <x-heroicon-s-plus class="size-5" />
                    </span>
                @endif
                {{-- A long name takes a second line on a phone; it is never cut short. --}}
                <span class="leading-snug">{{ $name }}</span>
            </a>

            <x-site.nav />

            {{--
                Language, the light or dark look, and the way to the staff page are tucked behind one button,
                so the bar holds only the menu and the emergency number. Closed by resources/js/menu.js like
                the groups of the menu.
            --}}
            <details class="menu-group relative ml-auto shrink-0" data-menu>
                <summary class="flex size-10 cursor-pointer list-none items-center justify-center rounded-md text-muted hover:bg-raised hover:text-ink" aria-label="{{ __('Language and display') }}" title="{{ __('Language and display') }}">
                    <x-heroicon-o-adjustments-horizontal class="size-5" aria-hidden="true" />
                </summary>
                <div class="menu-list left-auto right-0 space-y-3 p-3">
                    <div>
                        <p class="mb-1 text-xs text-muted">{{ __('Language') }}</p>
                        <x-site.language-switch />
                    </div>
                    <div>
                        <p class="mb-1 text-xs text-muted">{{ __('Theme') }}</p>
                        <x-site.theme-switch />
                    </div>
                    {{-- What staff use is kept apart from the public menu (resources/views/staff.blade.php). --}}
                    <a href="{{ route('staff') }}" class="flex items-center gap-2 border-t border-line pt-3">
                        <x-heroicon-m-identification class="size-4" aria-hidden="true" />{{ __('For staff') }}
                    </a>
                </div>
            </details>

            @if ($emergency)
                <a href="{{ $dial($emergency) }}" class="btn btn-danger shrink-0 min-h-10 px-4 py-2 text-sm max-sm:px-3">
                    <x-heroicon-s-phone class="size-4" aria-hidden="true" />
                    <span><span class="hidden sm:inline">{{ __('Emergency') }} </span><span class="font-mono">{{ $emergency }}</span></span>
                </a>
            @endif
        </div>
    </header>

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-line bg-surface">
        <div class="wrap grid gap-10 py-12 md:grid-cols-3">
            <div>
                <p class="text-lg font-semibold">{{ $name }}</p>
                @if ($address)
                    <p class="mt-3 whitespace-pre-line text-muted">{{ $address }}</p>
                @endif
                {{-- Pages written in the panel that asked to be linked here (App\Models\SitePage). --}}
                @if ($pages->isNotEmpty())
                    <ul class="mt-4 space-y-2">
                        @foreach ($pages as $page)
                            <li><a href="{{ route('pages.show', $page) }}" class="text-ink underline decoration-line underline-offset-4 hover:text-brand">{{ $page->localTitle() }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <h2 class="eyebrow">{{ __('Contact us') }}</h2>
                <ul class="mt-3 space-y-2">
                    @if ($phone)
                        <li>{{ __('Phone') }}: <a href="{{ $dial($phone) }}" class="font-mono font-semibold text-brand underline">{{ $phone }}</a></li>
                    @endif
                    @if ($emergency)
                        <li>{{ __('Emergency') }}: <a href="{{ $dial($emergency) }}" class="font-mono font-semibold text-danger underline">{{ $emergency }}</a></li>
                    @endif
                    @if ($email)
                        <li>{{ __('Email') }}: <a href="mailto:{{ $email }}" class="font-semibold text-brand underline break-all">{{ $email }}</a></li>
                    @endif
                </ul>
            </div>

            @if ($social)
                <div>
                    <h2 class="eyebrow">{{ __('Follow us') }}</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($social as $network => $url)
                            <li><a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand underline">{{ $network }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="border-t border-line">
            <div class="wrap py-4">
                {{-- The Buddhist year for a reader in Thai. --}}
                <p class="font-mono text-sm text-muted">&copy; {{ app()->getLocale() === 'th' ? now()->year + 543 : now()->year }} {{ $name }}</p>
            </div>
        </div>
    </footer>

    <x-site.mobile-tabs />
</body>
</html>
