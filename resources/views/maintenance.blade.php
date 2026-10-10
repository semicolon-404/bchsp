{{--
    Shown to visitors while the site is closed from the panel (App\Http\Middleware\MaintenanceMode).
    Stands on its own: no scripts, no stylesheet to load, so it shows whatever is being worked on.
    The phone numbers stay: someone who came for them must still find them.
--}}
@php
    $phone = \App\Support\SiteSettings::get('contact.phone');
    $emergency = \App\Support\SiteSettings::get('contact.emergency_phone');
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if (\App\Support\Theme::isMourning()) style="filter: grayscale(1)" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('Back soon') }} · {{ \App\Support\SiteSettings::name() }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box;
               background: #ffffff; color: #18181b; font-family: 'Sarabun', 'Noto Sans Thai', system-ui, sans-serif; font-size: 1.125rem; text-align: center; }
        main { max-width: 32rem; }
        img { max-height: 72px; max-width: 100%; margin-bottom: 24px; }
        h1 { margin: 0 0 12px; font-size: 2rem; }
        p { margin: 0 0 12px; line-height: 1.8; color: #3f3f46; }
        a { color: #0f5f5c; font-weight: 600; }
        .emergency { margin-top: 24px; padding: 16px; border: 2px solid #b91c1c; border-radius: 12px; }
        .emergency a { color: #b91c1c; font-size: 1.75rem; }
    </style>
</head>
<body>
    <main>
        @if ($logo = \App\Support\SiteSettings::logoUrl())
            <img src="{{ $logo }}" alt="">
        @endif
        <h1>{{ __('Back soon') }}</h1>
        <p>{{ $message ?: __('We are working on the website right now. Please come back in a little while.') }}</p>
        <p><strong>{{ \App\Support\SiteSettings::name() }}</strong></p>
        @if ($phone)
            <p>{{ __('Phone') }}: <a href="{{ $dial($phone) }}">{{ $phone }}</a></p>
        @endif
        @if ($emergency)
            <p class="emergency">{{ __('Emergency') }}<br><a href="{{ $dial($emergency) }}">{{ $emergency }}</a></p>
        @endif
    </main>
</body>
</html>
