{{--
    The page shown when something goes wrong (404, 403, 419, 429, 500, 503): each of resources/views/errors/*.blade.php
    hands this its number and its words. It stands on its own: no stylesheet, no script, no database, so it shows
    whatever it was that failed. It reads like a notice from the hospital: its name at the head, what happened in plain
    words, the ways out (the home page, the announcements, the search) and the numbers to call. Behind it a few kapok
    seeds drift up, the site's own idea of itself: light as kapok. They move by transform alone, and not at all for
    a visitor who asks for less motion.

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
    // Where each seed starts across the page, its size, how long it takes to rise, its delay, how far it drifts, and
    // where it rests for a visitor who asks for less motion.
    $seeds = [[8, 46, 26, 0, 40, 30], [22, 32, 34, -9, -30, 60], [41, 40, 30, -4, 50, 15], [63, 54, 24, -14, -40, 45], [79, 34, 36, -20, 30, 70], [91, 42, 28, -6, -50, 25], [54, 28, 40, -26, 20, 80]];
    // The threads of one seed, drawn once.
    $threads = collect(range(0, 345, 15))->map(fn ($angle) => [round(cos(deg2rad($angle)) * (11 + $angle * 7 % 6), 2), round(sin(deg2rad($angle)) * (11 + $angle * 7 % 6), 2)]);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · {{ $name }}</title>
    <style>
        :root { --page: #f7f8f6; --paper: #ffffff; --ink: #1c1f1d; --muted: #5b625d; --line: #dfe3df; --brand: #166534; --brand-soft: #e8f2ea; --danger: #b91c1c; --seed: #9aa79d; }
        @media (prefers-color-scheme: dark) {
            :root { --page: #0d0f0e; --paper: #151816; --ink: #eef1ee; --muted: #a3aba5; --line: #2a2f2c; --brand: #4ade80; --brand-soft: #16251b; --danger: #f87171; --seed: #6f7c73; }
        }
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; background: var(--page); color: var(--ink);
               font-family: 'IBM Plex Sans Thai', 'Sarabun', 'Noto Sans Thai', system-ui, sans-serif; font-size: 1.0625rem; line-height: 1.75; overflow-x: hidden; }

        /* The head of the notice: the hospital's name over a fine green rule. */
        .head { border-top: 4px solid var(--brand); border-bottom: 1px solid var(--line); background: var(--paper); }
        .head-in { max-width: 44rem; margin: 0 auto; padding: 14px 24px; display: flex; align-items: center; gap: 12px; }
        .mark { width: 34px; height: 34px; flex: none; display: grid; place-items: center; border-radius: 8px; background: var(--brand); color: var(--paper); font-size: 1.4rem; font-weight: 700; line-height: 1; }
        .head-name { font-weight: 600; text-decoration: none; color: var(--ink); }

        main { position: relative; flex: 1; display: flex; align-items: center; }
        .notice { position: relative; z-index: 1; width: 100%; max-width: 44rem; margin: 0 auto; padding: 56px 24px 64px; }
        .label { display: inline-flex; align-items: center; gap: 8px; margin: 0 0 18px; padding: 4px 12px; border-radius: 999px; background: var(--brand-soft); color: var(--brand); font-size: 0.875rem; font-weight: 600; }
        .label b { font-family: ui-monospace, 'JetBrains Mono', monospace; }
        h1 { margin: 0 0 10px; font-size: clamp(1.75rem, 5vw, 2.35rem); line-height: 1.3; font-weight: 700; text-wrap: balance; }
        .message { margin: 0; max-width: 34rem; color: var(--muted); }

        /* The ways on: a plain list, each a line with an arrow, as a notice would give them. */
        .ways { margin: 32px 0 0; padding: 0; list-style: none; border-top: 1px solid var(--line); max-width: 30rem; }
        .ways li { border-bottom: 1px solid var(--line); }
        .ways a { display: flex; justify-content: space-between; align-items: center; min-height: 52px; padding: 10px 4px; color: var(--ink); font-weight: 600; text-decoration: none; }
        .ways a::after { content: "\2192"; color: var(--muted); transition: transform 0.2s ease; }
        .ways a:hover, .ways a:focus-visible { color: var(--brand); }
        .ways a:hover::after { transform: translateX(4px); color: var(--brand); }
        .ways .first a { color: var(--brand); }

        .foot { position: relative; z-index: 1; border-top: 1px solid var(--line); background: var(--paper); font-size: 0.9375rem; color: var(--muted); }
        .foot-in { max-width: 44rem; margin: 0 auto; padding: 16px 24px; display: flex; flex-wrap: wrap; gap: 4px 18px; }
        .foot a { color: var(--brand); font-weight: 700; white-space: nowrap; text-decoration: none; }
        .foot a.emergency { color: var(--danger); }
        a:focus-visible { outline: 3px solid var(--brand); outline-offset: 2px; border-radius: 4px; }

        /* Kapok seeds rising slowly behind the notice: light as kapok. Transform and opacity only. */
        .seeds { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
        .seed { position: absolute; bottom: -60px; left: var(--x); width: var(--s); height: var(--s); color: var(--seed); opacity: 0;
                animation: rise var(--t) linear var(--d) infinite; }
        .seed svg { display: block; width: 100%; height: 100%; animation: sway 7s ease-in-out infinite alternate; }
        @keyframes rise {
            0% { transform: translate3d(0, 0, 0) rotate(0deg); opacity: 0; }
            12% { opacity: 0.8; }
            85% { opacity: 0.5; }
            100% { transform: translate3d(var(--drift), -115vh, 0) rotate(160deg); opacity: 0; }
        }
        @keyframes sway { from { transform: translateX(-10px); } to { transform: translateX(10px); } }
        @media (prefers-reduced-motion: reduce) { .seed, .seed svg { animation: none; } .seed { opacity: 0.35; bottom: var(--rest); } }
        @media (max-width: 40rem) { .seed:nth-child(n+5) { display: none; } }
    </style>
</head>
<body>
    <header class="head">
        <div class="head-in">
            <span class="mark" aria-hidden="true">+</span>
            <a class="head-name" href="{{ url('/') }}">{{ $name }}</a>
        </div>
    </header>

    <main>
        {{-- One seed drawn once: a soft centre and fine threads around it, as kapok floss. --}}
        <svg width="0" height="0" style="position: absolute" aria-hidden="true">
            <symbol id="kapok" viewBox="-20 -20 40 40">
                <g stroke="currentColor" stroke-width="0.6" stroke-linecap="round" fill="none">
                    @foreach ($threads as [$x, $y])
                        <line x1="0" y1="0" x2="{{ $x }}" y2="{{ $y }}" />
                    @endforeach
                </g>
                <circle r="2.2" fill="currentColor" />
            </symbol>
        </svg>
        <div class="seeds" aria-hidden="true">
            @foreach ($seeds as [$x, $size, $time, $wait, $drift, $rest])
                <span class="seed" style="--x: {{ $x }}%; --s: {{ $size }}px; --t: {{ $time }}s; --d: {{ $wait }}s; --drift: {{ $drift }}px; --rest: {{ $rest }}%"><svg><use href="#kapok" /></svg></span>
            @endforeach
        </div>

        <div class="notice">
            <p class="label">{{ __('Error') }} <b>{{ $code }}</b></p>
            <h1>{{ $title }}</h1>
            <p class="message">{{ $message }}</p>

            @if ($ways)
                <ul class="ways">
                    <li class="first"><a href="{{ url('/') }}">{{ __('Home') }}</a></li>
                    <li><a href="{{ url('/announcements') }}">{{ __('Announcements') }}</a></li>
                    <li><a href="{{ url('/search') }}">{{ __('Search') }}</a></li>
                    <li><a href="{{ url('/menu') }}">{{ __('Everything on this website') }}</a></li>
                </ul>
            @endif
        </div>
    </main>

    @if ($phone || $emergency)
        <footer class="foot">
            <div class="foot-in">
                <span>{{ $name }}</span>
                @if ($phone) <span>{{ __('Phone') }} <a href="{{ $dial($phone) }}">{{ $phone }}</a></span> @endif
                @if ($emergency) <span>{{ __('Emergency') }} <a class="emergency" href="{{ $dial($emergency) }}">{{ $emergency }}</a></span> @endif
            </div>
        </footer>
    @endif
</body>
</html>
