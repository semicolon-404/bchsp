{{--
    The report on a page of its own, laid out for paper: opened from the panel's Reports page, it brings up the
    browser's print window, where "Save as PDF" makes the file to send to the executives. No scripts but that one.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('Website report') }} · {{ $title }} · {{ config('app.name') }}</title>
    <style>
        @page { size: A4; margin: 18mm 16mm; }
        body { margin: 0 auto; max-width: 190mm; padding: 16px; color: #111; background: #fff; font-family: 'Sarabun', 'TH Sarabun New', 'Leelawadee UI', Tahoma, sans-serif; line-height: 1.6; }
        header { margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #111; }
        h1 { margin: 0; font-size: 1.4rem; }
        h3 { break-after: avoid; }
        table { break-inside: auto; }
        tr { break-inside: avoid; }
        .meta { margin: 4px 0 0; font-size: .9rem; color: #444; }
        .screen-only { margin: 0 0 16px; }
        .screen-only button { padding: 8px 16px; font: inherit; cursor: pointer; }
        @media print { .screen-only { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <p class="screen-only"><button type="button" onclick="window.print()">{{ __('Print or save as PDF') }}</button></p>

    <header>
        <h1>{{ config('app.name') }}</h1>
        <p class="meta">{{ __('Website report') }}: {{ $title }} ({{ $summary['from']->format('d/m/').($summary['from']->year + 543) }} – {{ $summary['until']->format('d/m/').($summary['until']->year + 543) }})</p>
        <p class="meta">{{ __('Made on') }} {{ now()->format('d/m/').(now()->year + 543) }} {{ now()->format('H:i') }} · {{ __('by') }} {{ auth()->user()->name }}</p>
    </header>

    @include('reports.body', ['summary' => $summary])

    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>
