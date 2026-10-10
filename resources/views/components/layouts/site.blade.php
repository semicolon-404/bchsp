{{--
    The frame of every public page: the bar at the top, the page itself, the footer.

    <x-layouts.site title="Contact" description="How to reach us">...</x-layouts.site>

    Without a title the page is named after the hospital alone.
--}}
@props(['title' => null, 'description' => null])
@php
    use App\Support\SiteSettings;

    $name = config('app.name');
    $logo = SiteSettings::logoUrl();
    $phone = SiteSettings::get('contact.phone');
    $emergency = SiteSettings::get('contact.emergency_phone');
    $address = SiteSettings::get('contact.address');
    $email = SiteSettings::get('contact.email');
    $social = SiteSettings::socialLinks();
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · '.$name : $name }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    <x-site.prefs />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col font-sans antialiased">
    <a href="#main" class="btn btn-brand sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50">{{ __('Skip to the content') }}</a>

    <x-site.tools />

    <header class="sticky top-0 z-40 border-b border-line bg-page">
        <div class="wrap flex min-h-18 items-center justify-between gap-3 py-2">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 font-bold text-ink no-underline">
                @if ($logo)
                    <img src="{{ $logo }}" alt="" class="h-11 w-auto shrink-0">
                @else
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand text-on-brand" aria-hidden="true">
                        <x-heroicon-s-plus class="size-7" />
                    </span>
                @endif
                {{-- A long name takes a second line on a phone; it is never cut short. --}}
                <span class="text-base leading-snug sm:text-xl">{{ $name }}</span>
            </a>

            @if ($emergency)
                <a href="{{ $dial($emergency) }}" class="btn btn-danger shrink-0">
                    <x-heroicon-s-phone class="size-5" aria-hidden="true" />
                    <span><span class="hidden sm:inline">{{ __('Emergency') }} </span>{{ $emergency }}</span>
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
                <p class="text-lg font-bold">{{ $name }}</p>
                @if ($address)
                    <p class="mt-3 whitespace-pre-line text-muted">{{ $address }}</p>
                @endif
            </div>

            <div>
                <h2 class="text-lg font-bold">{{ __('Contact us') }}</h2>
                <ul class="mt-3 space-y-2">
                    @if ($phone)
                        <li>{{ __('Phone') }}: <a href="{{ $dial($phone) }}" class="font-semibold text-brand underline">{{ $phone }}</a></li>
                    @endif
                    @if ($emergency)
                        <li>{{ __('Emergency') }}: <a href="{{ $dial($emergency) }}" class="font-semibold text-danger underline">{{ $emergency }}</a></li>
                    @endif
                    @if ($email)
                        <li>{{ __('Email') }}: <a href="mailto:{{ $email }}" class="font-semibold text-brand underline break-all">{{ $email }}</a></li>
                    @endif
                </ul>
            </div>

            @if ($social)
                <div>
                    <h2 class="text-lg font-bold">{{ __('Follow us') }}</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($social as $network => $url)
                            <li><a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand underline">{{ $network }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="border-t border-line">
            <p class="wrap py-5 text-sm text-muted">&copy; {{ now()->year + 543 }} {{ $name }}</p>
        </div>
    </footer>
</body>
</html>
