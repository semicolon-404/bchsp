{{--
    The frame of every public page: the bar at the top, the page itself, the footer.

    <x-layouts.site title="Contact" description="How to reach us">...</x-layouts.site>

    Without a title the page is named after the hospital alone.
--}}
@props(['title' => null, 'description' => null, 'noindex' => false, 'image' => null])
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
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" @class(['is-mourning' => App\Support\Theme::isMourning()])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · '.$name : $name }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @if ($noindex)
        <meta name="robots" content="noindex">
    @else
        {{-- One address for a page, whatever was added to it; and how a link to it looks when shared. --}}
        <link rel="canonical" href="{{ url()->current() }}">
        {{-- The small picture in a browser's tab: the hospital's logo, or the site's own mark (SeoController::icon). --}}
        <link rel="icon" type="image/png" sizes="192x192" href="{{ route('icon', ['size' => 192]) }}">
        {{-- For a phone that keeps the site on its home screen (SeoController::manifest). --}}
        <link rel="manifest" href="{{ route('manifest') }}">
        <link rel="apple-touch-icon" href="{{ route('icon', ['size' => 180]) }}">
        <meta name="theme-color" content="{{ App\Support\Theme::accent() }}">
        {{-- For a reader's news program: the newest announcements as a feed. --}}
        <link rel="alternate" type="application/rss+xml" title="{{ $name }}" href="{{ route('feed') }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $name }}">
        <meta property="og:title" content="{{ $title ?: $name }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:locale" content="{{ app()->getLocale() === 'th' ? 'th_TH' : 'en_US' }}">
        @if ($description)
            <meta property="og:description" content="{{ $description }}">
        @endif
        @if ($image ?: $logo)
            <meta property="og:image" content="{{ url($image ?: $logo) }}">
        @endif
        @if (request()->routeIs('home'))
            {{-- What the hospital is, for search engines: its name, address and numbers, from the site settings. --}}
            <script type="application/ld+json">{!! json_encode(array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Hospital',
                'name' => $name,
                'url' => route('home'),
                'telephone' => $phone,
                'email' => $email,
                'address' => $address ? ['@type' => 'PostalAddress', 'streetAddress' => $address, 'addressCountry' => 'TH'] : null,
                'logo' => $logo ? url($logo) : null,
            ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endif
    @endif
    {{-- Runs before the first paint so a dark-mode visitor never sees a light flash. The site opens light. Same storage key as the admin panel; see resources/js/theme.js. --}}
    <script>
        (function () {
            var mode = 'light';
            try { mode = localStorage.getItem('theme') || 'light'; } catch (e) {}
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
<body data-worker="{{ route('worker') }}" data-lightbox-close="{{ __('Close') }}" data-lightbox-previous="{{ __('Previous') }}" data-lightbox-next="{{ __('Next') }}" class="has-dock flex min-h-screen flex-col font-sans antialiased">
    <a href="#main" class="btn btn-brand sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50">{{ __('Skip to the content') }}</a>

    <x-site.alert />

    <header class="sticky top-0 z-40 border-b border-line bg-page/90 backdrop-blur">
        <div class="wrap flex min-h-16 items-center gap-3 py-2">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 font-semibold text-ink no-underline">
                {{-- Switched off in Site settings: the name alone, with neither the logo nor the mark that stands in for it. --}}
                @if (! SiteSettings::showsLogo())
                @elseif ($logo)
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
            <a href="{{ route('search') }}" class="ml-auto flex size-10 shrink-0 items-center justify-center rounded-md text-muted hover:bg-raised hover:text-ink lg:ml-0" aria-label="{{ __('Search') }}" title="{{ __('Search') }}">
                <x-heroicon-o-magnifying-glass class="size-5" aria-hidden="true" />
            </a>

            <details class="menu-group relative shrink-0" data-menu>
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

    {{-- The home page and the hospital's profile are made of sections that fill the width: the last one meets the footer, with no gap between. --}}
    <footer @class(['border-t border-line bg-surface', 'mt-16' => ! request()->routeIs('home', 'about', 'announcements.show')])>
        {{-- As many columns as there is something for: the hospital, contact, social links, and the administrator's own groups of links (App\Support\Footer). --}}
        <div class="wrap grid gap-10 py-12 sm:grid-cols-2 lg:grid-cols-[repeat(auto-fit,minmax(13rem,1fr))]">
            <div>
                <p class="flex items-center gap-3 text-lg font-semibold">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="" class="h-10 w-auto shrink-0">
                    @else
                        <span class="flex size-10 shrink-0 items-center justify-center rounded bg-brand text-on-brand" aria-hidden="true"><x-heroicon-s-plus class="size-6" /></span>
                    @endif
                    {{ $name }}
                </p>
                @if ($about = App\Support\Footer::about())
                    <p class="mt-3 whitespace-pre-line text-muted">{{ $about }}</p>
                @endif
                @if ($address)
                    <p class="mt-3 whitespace-pre-line text-muted">{{ $address }}</p>
                @endif
                {{-- The map itself is on the contact page, loaded when asked for: here is the way to it. --}}
                <p class="mt-3"><a href="{{ route('contact') }}" class="inline-flex items-center gap-2 font-semibold text-brand underline"><x-heroicon-m-map-pin class="size-4" aria-hidden="true" />{{ __('Map and directions') }}</a></p>
                {{-- Pages written in the panel that asked to be linked here (App\Models\SitePage). --}}
                @if ($pages->isNotEmpty())
                    <ul class="mt-4 space-y-2">
                        @foreach ($pages as $page)
                            <li><a href="{{ route('pages.show', $page) }}" class="text-ink underline decoration-line underline-offset-4 hover:text-brand">{{ $page->localTitle() }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- The parts of the site people look for most, one press from the foot of any page. --}}
            <div>
                <h2 class="eyebrow">{{ __('Explore') }}</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ([
                        ['announcements.index', 'Announcements'], ['schedule', 'Clinic schedule'], ['departments.index', 'Departments'],
                        ['downloads', 'Downloads'], ['ita', 'ITA'], ['about', 'About us'], ['menu', 'Everything on this website'],
                    ] as [$place, $label])
                        <li><a href="{{ route($place) }}" class="inline-flex items-center gap-2 text-ink no-underline hover:text-brand"><x-heroicon-m-chevron-right class="size-4 text-brand" aria-hidden="true" />{{ __($label) }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h2 class="eyebrow">{{ __('Contact us') }}</h2>
                <ul class="mt-3 space-y-3">
                    @if ($phone)
                        <li class="flex items-start gap-3"><x-heroicon-o-phone class="mt-1 size-5 shrink-0 text-brand" aria-hidden="true" /><span>{{ __('Phone') }}: <a href="{{ $dial($phone) }}" class="font-mono font-semibold text-brand underline">{{ $phone }}</a></span></li>
                    @endif
                    @if ($emergency)
                        <li class="flex items-start gap-3"><x-heroicon-o-bell-alert class="mt-1 size-5 shrink-0 text-danger" aria-hidden="true" /><span>{{ __('Emergency') }}: <a href="{{ $dial($emergency) }}" class="font-mono font-semibold text-danger underline">{{ $emergency }}</a></span></li>
                    @endif
                    @if ($email)
                        <li class="flex items-start gap-3"><x-heroicon-o-envelope class="mt-1 size-5 shrink-0 text-brand" aria-hidden="true" /><span class="min-w-0">{{ __('Email') }}: <a href="mailto:{{ $email }}" class="font-semibold text-brand underline break-all">{{ $email }}</a></span></li>
                    @endif
                    <li class="flex items-start gap-3"><x-heroicon-o-phone-arrow-up-right class="mt-1 size-5 shrink-0 text-brand" aria-hidden="true" /><a href="{{ route('phones') }}" class="text-ink underline decoration-line underline-offset-4 hover:text-brand">{{ __('Phone numbers of every department') }}</a></li>
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

            @foreach (App\Support\Footer::groups() as $heading => $links)
                <div>
                    <h2 class="eyebrow">{{ $heading }}</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($links as $link)
                            <li><a href="{{ $link['url'] }}" @if (parse_url($link['url'], PHP_URL_HOST) !== request()->getHost()) target="_blank" rel="noopener noreferrer" @endif class="text-ink underline decoration-line underline-offset-4 hover:text-brand">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="border-t border-line">
            <div class="wrap flex flex-wrap items-center justify-between gap-x-6 gap-y-1 py-4 font-mono text-sm text-muted">
                <p>{{ App\Support\Footer::copyright() }}</p>
                {{-- The newest release and its day: the site is looked after, and here is what changed (/updates). --}}
                @if ($release = App\Models\SiteUpdate::newest())
                    <p><a href="{{ route('updates') }}" class="text-muted underline decoration-line underline-offset-4 hover:text-brand">v{{ $release->version }} · {{ __('updated :date', ['date' => $release->releasedOn()]) }}</a></p>
                @endif
                {{-- Pages opened, as plain totals: nothing is kept about who opened them (App\Support\Visits). --}}
                @if (App\Support\Footer::showsVisits())
                    @php($visits = App\Support\Visits::totals())
                    <p>{{ __('Pages opened: today :today · this month :month · in all :all', ['today' => number_format($visits['today']), 'month' => number_format($visits['month']), 'all' => number_format($visits['all'])]) }}</p>
                @endif
            </div>
        </div>
    </footer>

    {{-- On a long page, the way back to its top: shown by resources/js/top.js once the reader is well down it. --}}
    <a href="#main" class="to-top" data-to-top hidden aria-label="{{ __('Back to the top') }}" title="{{ __('Back to the top') }}"><x-heroicon-m-arrow-up class="size-5" aria-hidden="true" /></a>

    <x-site.mobile-tabs />
</body>
</html>
