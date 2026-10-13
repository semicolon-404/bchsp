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
        :root { --page: #ffffff; --ink: #18181b; --muted: #52525b; --line: #d4d4d8; --surface: #f4f4f5; --brand: #15803d; --danger: #b91c1c; }
        @media (prefers-color-scheme: dark) {
            :root { --page: #0a0a0a; --ink: #f4f4f5; --muted: #a1a1aa; --line: #3f3f46; --surface: #18181b; --brand: #4ade80; --danger: #f87171; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               background: var(--page); color: var(--ink); font-family: 'IBM Plex Sans Thai', 'Sarabun', 'Noto Sans Thai', system-ui, sans-serif; font-size: 1.125rem; line-height: 1.7; text-align: center;
               background-image: radial-gradient(38rem 26rem at 50% 20%, color-mix(in srgb, var(--brand) 14%, transparent), transparent 70%); }
        main { width: 100%; max-width: 34rem; }
        img { max-height: 84px; max-width: 100%; margin-bottom: 20px; }
        .mark { display: inline-flex; width: 72px; height: 72px; margin-bottom: 20px; align-items: center; justify-content: center; border-radius: 20px; background: var(--brand); color: var(--page); font-size: 2.5rem; font-weight: 700; line-height: 1; }
        h1 { margin: 0 0 12px; font-size: 2.25rem; line-height: 1.25;
             background: linear-gradient(115deg, var(--ink) 30%, var(--brand)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        p { margin: 0 0 12px; color: var(--muted); }
        p strong { color: var(--ink); }
        a { color: var(--brand); font-weight: 700; }
        a:focus-visible { outline: 3px solid var(--brand); outline-offset: 2px; }
        .numbers { display: grid; gap: 12px; margin-top: 24px; }
        .number { display: block; padding: 16px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); color: var(--muted); text-decoration: none; }
        .number strong { display: block; color: var(--brand); font-size: 1.75rem; font-family: 'JetBrains Mono', ui-monospace, monospace; }
        .number.emergency { border-color: var(--danger); }
        .number.emergency, .number.emergency strong { color: var(--danger); }
    </style>
</head>
<body>
    <main>
        @if ($logo = \App\Support\SiteSettings::logoUrl())
            <img src="{{ $logo }}" alt="">
        @else
            <span class="mark" aria-hidden="true">+</span>
        @endif
        <h1>{{ __('Back soon') }}</h1>
        <p>{{ $message ?: __('We are working on the website right now. Please come back in a little while.') }}</p>
        <p><strong>{{ \App\Support\SiteSettings::name() }}</strong></p>
        @if ($phone || $emergency)
            <div class="numbers">
                @if ($emergency)
                    <a class="number emergency" href="{{ $dial($emergency) }}">{{ __('Emergency') }}<strong>{{ $emergency }}</strong></a>
                @endif
                @if ($phone)
                    <a class="number" href="{{ $dial($phone) }}">{{ __('Phone') }}<strong>{{ $phone }}</strong></a>
                @endif
            </div>
        @endif
    </main>
</body>
</html>
