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
                '@'.'context' => 'https://schema.org',
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
    {{-- The colour of the day, worn as a touch beside the site's own (Theme::touchOfTheDay): a line, a name, a dot. --}}
    @if ($day = App\Support\Theme::touchOfTheDay())
        <style>:root{--color-day:{{ $day }}}</style>
    @endif
</head>
<body data-worker="{{ route('worker') }}" data-lightbox-close="{{ __('Close') }}" data-lightbox-previous="{{ __('Previous') }}" data-lightbox-next="{{ __('Next') }}" class="has-dock flex min-h-screen flex-col font-sans antialiased">
    <a href="#main" class="btn btn-brand sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50">{{ __('Skip to the content') }}</a>

    <x-site.alert />

    {{-- Frosted glass, as a Mac's menu bar: the page shows faintly through it as it scrolls under. --}}
    <header class="sticky top-0 z-40 border-b border-line/70 bg-page/70 backdrop-blur-xl backdrop-saturate-150">
        @if ($day)
            <div class="day-line" aria-hidden="true"></div>
        @endif
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
    {{--
        The foot of every page, in three tiers:
        1. a band with the three numbers and ways in people look for at the end of a page (not on the home page, which ends with its own);
        2. the hospital on the left, then the site in columns of a few links each, grouped as a visitor thinks of them
           (services, news and documents, the organisation), then how to reach it; the administrator's own groups of
           links follow in a row of their own (App\Support\Footer);
        3. a thin bar: the year and name, the pages every site must have (privacy, the map of the site), and when the
           site was last updated.
    --}}
    @php
        $hours = trim((string) SiteSettings::get('contact.hours'));
        $drawn = App\Models\SitePage::published()->whereIn('slug', ['structure', 'executives'])->get()->keyBy('slug');
        $columns = array_filter([
            'Services' => [
                ['schedule', __('Clinic schedule')], ['departments.index', __('Departments')], ['phones', __('Phone numbers of every department')],
                App\Models\StaffLink::forThePublic()->exists() ? ['services', __('Online services')] : null, App\Models\Faq::shown()->exists() ? ['faq', __('Questions people ask')] : null,
            ],
            'News and documents' => [
                ['announcements.index', __('Announcements')], ['posts', __('Feed of the departments')], ['calendar', __('Calendar')],
                ['downloads', __('Downloads')], ['ita', __('ITA')],
            ],
            'The hospital' => [
                ['about', __('About us')],
                $drawn->has('structure') ? [route('pages.show', $drawn['structure']), $drawn['structure']->localTitle()] : null,
                $drawn->has('executives') ? [route('pages.show', $drawn['executives']), $drawn['executives']->localTitle()] : null,
                ['complaint', __('Complaints and suggestions')], ['contact', __('Contact us')],
            ],
        ]);
        $href = fn (string $place) => str_starts_with($place, 'http') ? $place : route($place);
    @endphp
    <footer @class(['site-foot', 'mt-16' => ! request()->routeIs('home', 'about', 'announcements.show')])>
        @unless (request()->routeIs('home'))
            {{-- 1. What people come to the foot of a page for. --}}
            <div class="foot-band">
                <div class="wrap grid gap-3 py-4 sm:grid-cols-3">
                    @if ($phone)
                        <a href="{{ $dial($phone) }}" class="foot-action">
                            <span class="foot-action-mark" aria-hidden="true"><x-heroicon-s-phone class="size-5" /></span>
                            <span class="min-w-0"><span class="block text-sm text-muted">{{ __('Call the hospital') }}</span><span class="block font-mono text-lg font-semibold">{{ $phone }}</span></span>
                        </a>
                    @endif
                    @if ($emergency)
                        <a href="{{ $dial($emergency) }}" class="foot-action is-danger">
                            <span class="foot-action-mark" aria-hidden="true"><x-heroicon-s-bell-alert class="size-5" /></span>
                            <span class="min-w-0"><span class="block text-sm text-muted">{{ __('Emergency, day and night') }}</span><span class="block font-mono text-lg font-semibold">{{ $emergency }}</span></span>
                        </a>
                    @endif
                    <a href="{{ route('contact') }}" class="foot-action">
                        <span class="foot-action-mark" aria-hidden="true"><x-heroicon-s-map-pin class="size-5" /></span>
                        <span class="min-w-0"><span class="block text-sm text-muted">{{ __('Find us') }}</span><span class="block text-lg font-semibold">{{ __('Map and directions') }}</span></span>
                    </a>
                </div>
            </div>
        @endunless

        {{--
            2. The hospital, the site in columns, how to reach it: the columns as they always were, kept low,
            small type and close lines, so the foot of the page is read at a glance and does not tower.
        --}}
        <div class="wrap grid grid-cols-2 gap-x-6 gap-y-6 py-8 sm:gap-x-8 lg:grid-cols-12">
            <div class="col-span-2 lg:col-span-3">
                <p class="flex items-center gap-3 font-semibold">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="" class="h-9 w-auto shrink-0">
                    @else
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand text-on-brand" aria-hidden="true"><x-heroicon-s-plus class="size-5" /></span>
                    @endif
                    <span class="leading-snug">{{ $name }}</span>
                </p>
                @if ($about = App\Support\Footer::about())
                    <p class="mt-2 max-w-sm whitespace-pre-line text-sm text-muted">{{ $about }}</p>
                @elseif ($motto = SiteSettings::get('site.motto'))
                    <p class="mt-2 max-w-sm text-sm text-muted">“{{ $motto }}”</p>
                @endif
                @if ($address)
                    <p class="mt-2 flex max-w-sm items-start gap-1.5 text-sm text-muted"><x-heroicon-m-map-pin class="mt-0.5 size-4 shrink-0 text-brand" aria-hidden="true" /><span class="whitespace-pre-line">{{ $address }}</span></p>
                @endif
            </div>

            @foreach ($columns as $heading => $links)
                <nav class="lg:col-span-2" aria-label="{{ __($heading) }}">
                    <h2 class="foot-heading">{{ __($heading) }}</h2>
                    <ul class="mt-2.5 space-y-1 text-sm">
                        @foreach (array_filter($links) as [$place, $label])
                            <li><a href="{{ $href($place) }}" class="foot-link">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach

            <div class="col-span-2 sm:col-span-1 lg:col-span-3">
                <h2 class="foot-heading">{{ __('Contact us') }}</h2>
                <ul class="mt-2.5 space-y-1 text-sm">
                    @if ($phone)
                        <li><a href="{{ $dial($phone) }}" class="foot-link font-mono">{{ $phone }}</a></li>
                    @endif
                    @if ($email)
                        <li><a href="mailto:{{ $email }}" class="foot-link break-all">{{ $email }}</a></li>
                    @endif
                    {{-- Where the hospital is followed: with the ways to reach it, a link like the others. --}}
                    @foreach ($social as $network => $url)
                        <li><a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="foot-link inline-flex items-center gap-1.5">{{ $network }}<x-heroicon-m-arrow-up-right class="size-3.5" aria-hidden="true" /><span class="sr-only"> {{ __('(opens in a new tab)') }}</span></a></li>
                    @endforeach
                    @if ($hours !== '')
                        <li class="whitespace-pre-line pt-1 text-xs text-muted">{{ $hours }}</li>
                    @endif
                </ul>
            </div>
        </div>

        @if ($groups = App\Support\Footer::groups())
            {{-- The administrator's own groups of links (Site settings > Footer), in a row of their own. --}}
            <div class="wrap grid gap-x-8 gap-y-6 border-t border-line py-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($groups as $heading => $links)
                    <nav aria-label="{{ $heading }}">
                        <h2 class="foot-heading">{{ $heading }}</h2>
                        <ul class="mt-4 space-y-2.5">
                            @foreach ($links as $link)
                                <li><a href="{{ $link['url'] }}" @if (parse_url($link['url'], PHP_URL_HOST) !== request()->getHost()) target="_blank" rel="noopener noreferrer" @endif class="foot-link">{{ $link['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endforeach
            </div>
        @endif

        {{--
            3. On one line: the year and name, the pages every site must have, the day, and the pages opened
            (plain totals: nothing is kept about who opened them, App\Support\Visits). It wraps on a narrow screen.
        --}}
        <div class="border-t border-line">
            <ul class="wrap flex flex-wrap items-center gap-x-5 gap-y-2 py-4 text-xs text-muted">
                <li class="mr-auto">{{ App\Support\Footer::copyright() }}</li>
                @foreach ($pages as $page)
                    <li><a href="{{ route('pages.show', $page) }}" class="foot-link">{{ $page->localTitle() }}</a></li>
                @endforeach
                <li><a href="{{ route('menu') }}" class="foot-link">{{ __('Everything on this website') }}</a></li>
                {{-- What changed on the site (/updates), by name: the numbers of releases are for the people who make it. --}}
                @if (App\Models\SiteUpdate::newest())
                    <li><a href="{{ route('updates') }}" class="foot-link">{{ __('What is new on this website') }}</a></li>
                @endif
                @if (App\Support\Footer::showsVisits())
                    @php($visits = App\Support\Visits::totals())
                    <li class="font-mono">{{ __('Pages opened: today :today · this month :month · in all :all', ['today' => number_format($visits['today']), 'month' => number_format($visits['month']), 'all' => number_format($visits['all'])]) }}</li>
                @endif
                @if ($day)
                    <li class="inline-flex items-center gap-2 font-mono"><span class="day-dot" aria-hidden="true"></span>{{ now()->locale(app()->getLocale())->translatedFormat('D j M ').(now()->year + (app()->getLocale() === 'th' ? 543 : 0)) }}</li>
                @endif
            </ul>
        </div>
    </footer>

    {{-- On a long page, the way back to its top: shown by resources/js/top.js once the reader is well down it. --}}
    <a href="#main" class="to-top" data-to-top hidden aria-label="{{ __('Back to the top') }}" title="{{ __('Back to the top') }}"><x-heroicon-m-arrow-up class="size-5" aria-hidden="true" /></a>

    <x-site.mobile-tabs />
</body>
</html>
