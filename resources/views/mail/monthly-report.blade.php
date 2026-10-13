{{--
    App\Mail\MonthlyReport: the report the panel's Reports page shows (reports.body), in a card with the site's
    colour. Every style is on its element: mail programs know little of style sheets.
--}}
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
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e4e4e7;">
                    <tr><td style="height:6px;background:{{ App\Support\Theme::accent() }};font-size:0;line-height:0;">&nbsp;</td></tr>
                    <tr>
                        <td style="padding:28px 28px 4px;">
                            <p style="margin:0;font-size:13px;letter-spacing:.04em;color:#52525b;">{{ App\Support\SiteSettings::name() }}</p>
                            <h1 style="margin:8px 0 0;font-size:22px;line-height:1.35;">{{ __('Website report').': '.$monthName }}</h1>
                        </td>
                    </tr>
                    <tr><td style="padding:8px 28px 12px;font-size:15px;line-height:1.6;">@include('reports.body', ['summary' => $summary])</td></tr>
                    <tr>
                        <td style="padding:8px 28px 28px;">
                            <a href="{{ url('/admin/reports') }}" style="display:inline-block;padding:12px 22px;border-radius:10px;background:#18181b;color:#ffffff;font-weight:bold;text-decoration:none;font-size:15px;">{{ __('Open the reports') }} &rarr;</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
