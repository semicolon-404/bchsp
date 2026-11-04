{{--
    /offline: what a phone shows when the site is opened with no connection (kept on the phone by the
    service worker, SeoController::worker). Stands on its own: no stylesheet or script to fetch. It holds the
    one thing that must be reachable without a network: the numbers to call.

    Laid out as a card of the hospital's, quiet: who it is from, what happened in a line, then the numbers
    as rows to press, the emergency one first, and the way to try again.
--}}
@php
    $name = \App\Support\SiteSettings::name();
    $phone = \App\Support\SiteSettings::get('contact.phone');
    $emergency = \App\Support\SiteSettings::get('contact.emergency_phone');
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
    $call = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.148a1.5 1.5 0 0 1 1.465 1.175l.513 2.31a1.5 1.5 0 0 1-1.059 1.767l-.95.273a11.04 11.04 0 0 0 5.858 5.858l.273-.95a1.5 1.5 0 0 1 1.767-1.059l2.31.513A1.5 1.5 0 0 1 18 13.352V14.5a1.5 1.5 0 0 1-1.5 1.5H15C7.82 16 2 10.18 2 3V3.5Z" clip-rule="evenodd"/></svg>';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#15803d">
    <title>{{ __('No connection') }} · {{ $name }}</title>
    <style>
        :root {
            --page: #f4f5f4; --card: #ffffff; --ink: #18181b; --muted: #5f6368; --line: #e4e6e4;
            --brand: #15803d; --brand-soft: #e8f3ec; --danger: #b91c1c; --danger-soft: #fdecec;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --page: #0b0d0c; --card: #151816; --ink: #f1f3f1; --muted: #a3a9a5; --line: #262b28;
                --brand: #4ade80; --brand-soft: #15291c; --danger: #f87171; --danger-soft: #2c1414;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 20px;
            background: var(--page); color: var(--ink);
            font-family: 'IBM Plex Sans Thai', 'Sarabun', 'Noto Sans Thai', system-ui, sans-serif; font-size: 1rem; line-height: 1.7;
        }
        main { width: 100%; max-width: 26rem; }
        .from { display: flex; align-items: center; gap: 10px; margin: 0 0 14px 4px; font-size: .875rem; font-weight: 600; color: var(--muted); }
        .from i { width: 8px; height: 8px; border-radius: 50%; background: #a1a1aa; box-shadow: 0 0 0 4px color-mix(in srgb, #a1a1aa 25%, transparent); }
        .card { overflow: hidden; border: 1px solid var(--line); border-radius: 20px; background: var(--card); box-shadow: 0 24px 48px -32px rgb(0 0 0 / .35); }
        .head { padding: 24px 24px 20px; }
        .icon { display: grid; place-items: center; width: 44px; height: 44px; margin-bottom: 16px; border-radius: 12px; background: var(--page); color: var(--muted); }
        .icon svg { width: 22px; height: 22px; }
        h1 { margin: 0; font-size: 1.25rem; font-weight: 700; line-height: 1.4; }
        .head p { margin: 6px 0 0; font-size: .9375rem; color: var(--muted); }
        .label { margin: 0; padding: 10px 24px; border-top: 1px solid var(--line); font-size: .75rem; font-weight: 600; letter-spacing: .04em; color: var(--muted); background: var(--page); }
        .call { display: flex; align-items: center; gap: 14px; padding: 16px 24px; border-top: 1px solid var(--line); color: var(--ink); text-decoration: none; }
        .call:hover { background: var(--brand-soft); }
        .call span { flex: 1; min-width: 0; }
        .call small { display: block; font-size: .8125rem; color: var(--muted); }
        .call strong { display: block; font-size: 1.375rem; font-weight: 700; letter-spacing: .01em; font-variant-numeric: tabular-nums; }
        .call b { display: grid; place-items: center; flex-shrink: 0; width: 44px; height: 44px; border-radius: 50%; color: #fff; background: var(--brand); }
        .call b svg { width: 20px; height: 20px; }
        .call.is-danger strong { color: var(--danger); }
        .call.is-danger b { background: var(--danger); }
        .call.is-danger:hover { background: var(--danger-soft); }
        .foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 24px; border-top: 1px solid var(--line); }
        .foot span { font-size: .8125rem; color: var(--muted); }
        button { padding: 10px 18px; border: 0; border-radius: 10px; background: var(--ink); color: var(--card); font: inherit; font-size: .9375rem; font-weight: 600; cursor: pointer; }
        button:hover { opacity: .88; }
        button:focus-visible, .call:focus-visible { outline: 3px solid var(--brand); outline-offset: 2px; }
    </style>
</head>
<body>
    <main>
        <p class="from"><i aria-hidden="true"></i>{{ $name }}</p>
        <div class="card">
            <div class="head">
                <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M8.5 16.4a5 5 0 0 1 7 0M5 12.9a10 10 0 0 1 4.2-2.5M14.8 10.4A10 10 0 0 1 19 12.9M2 9.3a15 15 0 0 1 4.3-2.6M17.7 6.7A15 15 0 0 1 22 9.3M12 20h.01"/></svg></span>
                <h1>{{ __('No connection') }}</h1>
                <p>{{ __('This page could not be opened: the phone is not connected to the internet. The numbers below still work.') }}</p>
            </div>

            @if ($emergency || $phone)
                <p class="label">{{ __('Call') }}</p>
            @endif
            @if ($emergency)
                <a class="call is-danger" href="{{ $dial($emergency) }}"><span><small>{{ __('Emergency, 24 hours a day') }}</small><strong>{{ $emergency }}</strong></span><b>{!! $call !!}</b></a>
            @endif
            @if ($phone)
                <a class="call" href="{{ $dial($phone) }}"><span><small>{{ $name }}</small><strong>{{ $phone }}</strong></span><b>{!! $call !!}</b></a>
            @endif

            <div class="foot">
                <span>{{ __('Connected again?') }}</span>
                <button type="button" onclick="location.reload()">{{ __('Try again') }}</button>
            </div>
        </div>
    </main>
</body>
</html>
