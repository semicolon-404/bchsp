{{--
    App\Mail\SystemProblems, as a page: the same words as the plain text (system-problems.blade.php), set out as
    a card with the site's colour. Mail programs know little of style sheets, so every style is on its element
    and the layout is a table.
--}}
@php
    $accent = App\Support\Theme::accent();
    $tones = ['problem' => ['#b91c1c', '#fee2e2'], 'warning' => ['#92400e', '#fef3c7']];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ App\Support\SiteSettings::name() }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:'Leelawadee UI',Tahoma,Arial,sans-serif;color:#18181b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e4e4e7;">
                    <tr><td style="height:6px;background:{{ $accent }};font-size:0;line-height:0;">&nbsp;</td></tr>
                    <tr>
                        <td style="padding:28px 28px 8px;">
                            <p style="margin:0;font-size:13px;letter-spacing:.04em;color:#52525b;">{{ App\Support\SiteSettings::name() }}</p>
                            <h1 style="margin:8px 0 0;font-size:22px;line-height:1.35;">{{ __('The website\'s daily check found :count thing(s) that need looking at:', ['count' => count($problems)]) }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 28px 4px;">
                            @foreach ($problems as $problem)
                                @php([$ink, $wash] = $tones[$problem['state']] ?? $tones['warning'])
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 12px;border:1px solid #e4e4e7;border-left:4px solid {{ $ink }};border-radius:10px;">
                                    <tr>
                                        <td style="padding:14px 16px;">
                                            <p style="margin:0;font-size:16px;font-weight:bold;">{{ __($problem['name']) }}</p>
                                            <p style="margin:6px 0 0;font-size:15px;line-height:1.6;"><span style="display:inline-block;padding:1px 8px;border-radius:999px;background:{{ $wash }};color:{{ $ink }};">{{ $problem['found'] }}</span></p>
                                            @if ($problem['advice'])
                                                <p style="margin:8px 0 0;font-size:15px;line-height:1.6;color:#3f3f46;">{{ $problem['advice'] }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 28px;">
                            <p style="margin:0 0 14px;font-size:15px;color:#3f3f46;">{{ __('See them all, and what to do, at:') }}</p>
                            <a href="{{ url('/admin/system-status') }}" style="display:inline-block;padding:12px 22px;border-radius:10px;background:#18181b;color:#ffffff;font-weight:bold;text-decoration:none;font-size:15px;">{{ __('System status') }} &rarr;</a>
                            <p style="margin:14px 0 0;font-size:13px;color:#71717a;word-break:break-all;">{{ url('/admin/system-status') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
