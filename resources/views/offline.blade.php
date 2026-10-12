{{--
    /offline: what a phone shows when the site is opened with no connection (kept on the phone by the
    service worker, SeoController::worker). Stands on its own: no stylesheet or script to fetch. It holds the
    one thing that must be reachable without a network: the numbers to call.
--}}
@php
    $phone = \App\Support\SiteSettings::get('contact.phone');
    $emergency = \App\Support\SiteSettings::get('contact.emergency_phone');
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('No connection') }} · {{ \App\Support\SiteSettings::name() }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box;
               background: #ffffff; color: #18181b; font-family: 'Sarabun', 'Noto Sans Thai', system-ui, sans-serif; font-size: 1.125rem; text-align: center; }
        main { max-width: 32rem; }
        h1 { margin: 0 0 12px; font-size: 1.75rem; }
        p { margin: 0 0 12px; line-height: 1.8; color: #3f3f46; }
        a { color: #15803d; font-weight: 600; }
        .number { display: block; margin-top: 16px; padding: 16px; border: 2px solid #15803d; border-radius: 12px; text-decoration: none; }
        .number strong { display: block; font-size: 1.75rem; }
        .emergency { border-color: #b91c1c; }
        .emergency, .emergency strong { color: #b91c1c; }
        button { margin-top: 24px; padding: 12px 24px; border: 1px solid #a1a1aa; border-radius: 8px; background: #f4f4f5; font: inherit; cursor: pointer; }
        @media (prefers-color-scheme: dark) {
            body { background: #0a0a0a; color: #f4f4f5; }
            p { color: #d4d4d8; }
            a { color: #4ade80; }
            .number { border-color: #4ade80; }
            .emergency { border-color: #f87171; }
            .emergency, .emergency strong { color: #f87171; }
            button { background: #18181b; color: #f4f4f5; border-color: #52525b; }
        }
    </style>
</head>
<body>
    <main>
        <h1>{{ __('No connection') }}</h1>
        <p>{{ __('This page could not be opened: the phone is not connected to the internet. The numbers below still work.') }}</p>

        @if ($emergency)
            <a class="number emergency" href="{{ $dial($emergency) }}">{{ __('Emergency, 24 hours a day') }}<strong>{{ $emergency }}</strong></a>
        @endif
        @if ($phone)
            <a class="number" href="{{ $dial($phone) }}">{{ \App\Support\SiteSettings::name() }}<strong>{{ $phone }}</strong></a>
        @endif

        <button type="button" onclick="location.reload()">{{ __('Try again') }}</button>
    </main>
</body>
</html>
