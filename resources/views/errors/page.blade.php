{{--
    The page shown when something goes wrong (404, 403, 419, 429, 500, 503): each of resources/views/errors/*.blade.php
    hands this its number and its words. It stands on its own: no stylesheet, no script, no database, so it shows
    whatever it was that failed. It says what happened in plain words, and gives the ways out: the home page, the
    announcements, the search, and the number to call, which someone who came for it must still find.

    @include('errors.page', ['code' => 404, 'title' => '...', 'message' => '...'])
--}}
@php
    // Read carefully: this page is shown when the site is in trouble, and must not add to it.
    $setting = function (string $key) {
        try {
            return \App\Support\SiteSettings::get($key);
        } catch (\Throwable) {
            return null;
        }
    };
    $name = (string) config('app.name');
    $phone = $setting('contact.phone');
    $emergency = $setting('contact.emergency_phone');
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
    $ways = $ways ?? true;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · {{ $name }}</title>
    <style>
        :root { --page: #ffffff; --ink: #18181b; --muted: #52525b; --line: #d4d4d8; --surface: #f4f4f5; --brand: #15803d; --danger: #b91c1c; }
        @media (prefers-color-scheme: dark) {
            :root { --page: #0a0a0a; --ink: #f4f4f5; --muted: #a1a1aa; --line: #3f3f46; --surface: #18181b; --brand: #4ade80; --danger: #f87171; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               background: var(--page); color: var(--ink); font-family: 'IBM Plex Sans Thai', 'Sarabun', 'Noto Sans Thai', system-ui, sans-serif; font-size: 1.125rem; line-height: 1.7; text-align: center;
               background-image: radial-gradient(38rem 26rem at 50% 20%, color-mix(in srgb, var(--brand) 14%, transparent), transparent 70%); }
        main { width: 100%; max-width: 36rem; }
        .code { margin: 0; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: clamp(4.5rem, 22vw, 8rem); font-weight: 700; line-height: 1;
                background: linear-gradient(115deg, var(--ink) 30%, var(--brand)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        h1 { margin: 12px 0 8px; font-size: 1.75rem; line-height: 1.3; }
        p { margin: 0 0 12px; color: var(--muted); }
        .ways { margin: 28px 0 0; padding: 0; list-style: none; display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
        .ways a { display: inline-block; min-height: 44px; padding: 10px 20px; border: 1px solid var(--line); border-radius: 10px; background: var(--surface); color: var(--ink); font-weight: 600; text-decoration: none; }
        .ways a:hover, .ways a:focus-visible { border-color: var(--brand); color: var(--brand); }
        .ways a.first { border-color: var(--brand); background: var(--brand); color: var(--page); }
        .numbers { margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--line); font-size: 1rem; }
        .numbers a { color: var(--brand); font-weight: 700; white-space: nowrap; }
        .numbers a.emergency { color: var(--danger); }
        a:focus-visible { outline: 3px solid var(--brand); outline-offset: 2px; }
    </style>
</head>
<body>
    <main>
        <p class="code" aria-hidden="true">{{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>

        @if ($ways)
            <ul class="ways">
                <li><a class="first" href="{{ url('/') }}">{{ __('Home') }}</a></li>
                <li><a href="{{ url('/announcements') }}">{{ __('Announcements') }}</a></li>
                <li><a href="{{ url('/search') }}">{{ __('Search') }}</a></li>
                <li><a href="{{ url('/menu') }}">{{ __('Everything on this website') }}</a></li>
            </ul>
        @endif

        @if ($phone || $emergency)
            <p class="numbers">
                {{ $name }}
                @if ($phone) · <a href="{{ $dial($phone) }}">{{ $phone }}</a> @endif
                @if ($emergency) · {{ __('Emergency') }} <a class="emergency" href="{{ $dial($emergency) }}">{{ $emergency }}</a> @endif
            </p>
        @endif
    </main>
</body>
</html>
