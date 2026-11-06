{{--
    The tables of the report, shared by the panel's page and the page for printing (App\Support\Reports::summary).
    Plain tables with inline styles: the same in the panel, on paper and in a PDF.
--}}
@php
    use App\Support\Visits;

    $cell = 'padding: 6px 10px; border-bottom: 1px solid rgba(128,128,128,.35); text-align: left; vertical-align: top;';
    $number = $cell.' text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap;';
    $heading = 'margin: 28px 0 8px; font-size: 1.05rem; font-weight: 600;';
    $table = 'width: 100%; border-collapse: collapse; font-size: .95rem;';
    $month = fn (string $key) => \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $key.'-01')->locale(app()->getLocale())->translatedFormat('M ').((int) substr($key, 0, 4) + 543);
    $largest = max(1, ...array_values($summary['visits']['monthly'] ?: [0]));
@endphp

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
    @foreach ([
        ['Pages opened', number_format($summary['visits']['total'])],
        ['Announcements published', number_format($summary['announcements']['total'])],
        ['ITA '.$summary['ita']['year'], $summary['ita']['items'] ? $summary['ita']['percent'].'%' : '–'],
    ] as [$label, $figure])
        <div style="padding: 14px; border: 1px solid rgba(128,128,128,.35); border-radius: 10px;">
            <div style="font-size: .85rem; opacity: .75;">{{ __($label) }}</div>
            <div style="font-size: 1.6rem; font-weight: 700;">{{ $figure }}</div>
        </div>
    @endforeach
</div>

<h3 style="{{ $heading }}">{{ __('Pages opened, month by month') }}</h3>
<table style="{{ $table }}">
    @forelse ($summary['visits']['monthly'] as $key => $views)
        <tr>
            <td style="{{ $cell }} width: 8rem;">{{ $month($key) }}</td>
            {{-- A bar drawn with a border, so that it prints (browsers leave backgrounds out of print by default). --}}
            <td style="{{ $cell }}"><div style="height: 0; border-top: 10px solid currentColor; opacity: .55; width: {{ max(1, round($views / $largest * 100)) }}%;"></div></td>
            <td style="{{ $number }} width: 6rem;">{{ number_format($views) }}</td>
        </tr>
    @empty
        <tr><td style="{{ $cell }}">{{ __('Nothing in this period.') }}</td></tr>
    @endforelse
</table>

<h3 style="{{ $heading }}">{{ __('Pages opened, by part of the site') }}</h3>
<table style="{{ $table }}">
    @forelse ($summary['visits']['sections'] as $section => $views)
        <tr>
            <td style="{{ $cell }}">{{ __(Visits::SECTIONS[$section] ?? $section) }}</td>
            <td style="{{ $number }}">{{ number_format($views) }}</td>
            <td style="{{ $number }} width: 5rem;">{{ $summary['visits']['total'] ? round($views / $summary['visits']['total'] * 100) : 0 }}%</td>
        </tr>
    @empty
        <tr><td style="{{ $cell }}">{{ __('Nothing in this period.') }}</td></tr>
    @endforelse
</table>

<h3 style="{{ $heading }}">{{ __('Announcements published, by type') }}</h3>
<table style="{{ $table }}">
    @foreach ($summary['announcements']['types'] as $type => $count)
        <tr><td style="{{ $cell }}">{{ $type }}</td><td style="{{ $number }}">{{ number_format($count) }}</td></tr>
    @endforeach
    <tr><td style="{{ $cell }} font-weight: 600;">{{ __('In all') }}</td><td style="{{ $number }} font-weight: 600;">{{ number_format($summary['announcements']['total']) }}</td></tr>
</table>

<h3 style="{{ $heading }}">{{ __('Most read') }}</h3>
<table style="{{ $table }}">
    <tr><th style="{{ $cell }}">{{ __('Title') }}</th><th style="{{ $cell }}">{{ __('Type') }}</th><th style="{{ $number }}">{{ __('Times opened') }}</th></tr>
    @forelse ($summary['announcements']['top'] as $one)
        <tr>
            <td style="{{ $cell }}">{{ $one->title }}</td>
            <td style="{{ $cell }} white-space: nowrap;">{{ $one->type?->name ?? '–' }}</td>
            <td style="{{ $number }}">{{ number_format($one->views) }}</td>
        </tr>
    @empty
        <tr><td style="{{ $cell }}" colspan="3">{{ __('Nothing published in this period has been opened yet.') }}</td></tr>
    @endforelse
</table>

<h3 style="{{ $heading }}">{{ __('Posts of the departments') }}</h3>
<table style="{{ $table }}">
    <tr><th style="{{ $cell }}">{{ __('Department') }}</th><th style="{{ $number }}">{{ __('Posts') }}</th><th style="{{ $number }}">{{ __('Times opened') }}</th><th style="{{ $number }}">{{ __('Found useful') }}</th></tr>
    @forelse ($summary['departments'] as $row)
        <tr>
            <td style="{{ $cell }}">{{ $row['name'] }}</td>
            <td style="{{ $number }}">{{ number_format($row['posts']) }}</td>
            <td style="{{ $number }}">{{ number_format($row['views']) }}</td>
            <td style="{{ $number }}">{{ number_format($row['helpful']) }}</td>
        </tr>
    @empty
        <tr><td style="{{ $cell }}" colspan="4">{{ __('No department posted in this period.') }}</td></tr>
    @endforelse
</table>

<h3 style="{{ $heading }}">{{ __('What readers searched for') }}</h3>
<p style="margin: 0 0 0.5rem; opacity: .75;">{{ __('Words that found nothing are what the site should say more about.') }}</p>
@foreach (['top' => 'Searched for most', 'empty' => 'Searched for and not found'] as $which => $name)
    <table style="{{ $table }} margin-bottom: 0.75rem;">
        <tr><th style="{{ $cell }}">{{ __($name) }}</th><th style="{{ $number }}">{{ __('Times') }}</th></tr>
        @forelse ($summary['searches'][$which] as $row)
            <tr><td style="{{ $cell }}">{{ $row['term'] }}</td><td style="{{ $number }}">{{ number_format($row['searches']) }}</td></tr>
        @empty
            <tr><td style="{{ $cell }}" colspan="2">{{ $which === 'top' ? __('Nobody searched in this period.') : __('Every search in this period found something.') }}</td></tr>
        @endforelse
    </table>
@endforeach

<h3 style="{{ $heading }}">{{ __('ITA, fiscal year :year', ['year' => $summary['ita']['year']]) }}</h3>
<p style="margin: 0;">
    @if ($summary['ita']['items'])
        {{ __(':done of :all items have documents', ['done' => $summary['ita']['with_evidence'], 'all' => $summary['ita']['items']]) }} ({{ $summary['ita']['percent'] }}%).
        @if ($summary['ita']['missing'])
            {{ __('Still without documents:') }} {{ implode(', ', $summary['ita']['missing']) }}
        @endif
    @else
        {{ __('No items have been entered for this year yet.') }}
    @endif
</p>

<h3 style="{{ $heading }}">{{ __('Work of the staff in the panel') }}</h3>
<table style="{{ $table }}">
    <tr>
        <th style="{{ $cell }}">{{ __('Name') }}</th><th style="{{ $cell }}">{{ __('Role') }}</th>
        <th style="{{ $number }}">{{ __('Created') }}</th><th style="{{ $number }}">{{ __('Changed') }}</th><th style="{{ $number }}">{{ __('Deleted') }}</th>
        <th style="{{ $number }}">{{ __('Sign-ins') }}</th><th style="{{ $number }}">{{ __('Last sign-in') }}</th>
    </tr>
    @foreach ($summary['staff'] as $row)
        <tr>
            <td style="{{ $cell }}">{{ $row['name'] }}</td><td style="{{ $cell }}">{{ __($row['role']) }}</td>
            <td style="{{ $number }}">{{ $row['created'] }}</td><td style="{{ $number }}">{{ $row['changed'] }}</td><td style="{{ $number }}">{{ $row['deleted'] }}</td>
            <td style="{{ $number }}">{{ $row['sign_ins'] }}</td><td style="{{ $number }}">{{ $row['last_sign_in']?->format('d/m/').($row['last_sign_in'] ? $row['last_sign_in']->year + 543 : '') ?: '–' }}</td>
        </tr>
    @endforeach
</table>
@php($c = $summary['complaints'])
<h3 style="{{ $heading }}">{{ __('Complaints and suggestions') }}</h3>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
    @foreach ([
        ['Received', number_format($c['total']), null],
        ['Finished', number_format($c['done']).($c['total'] ? ' ('.round($c['done'] * 100 / $c['total']).'%)' : ''), '#15803d'],
        ['Still open', number_format($c['open']), null],
        ['Past their days', number_format($c['late']), $c['late'] ? '#b91c1c' : null],
    ] as [$label, $figure, $colour])
        <div style="padding: 12px; border: 1px solid rgba(128,128,128,.35); border-radius: 10px;">
            <div style="font-size: .85rem; opacity: .75;">{{ __($label) }}</div>
            <div style="font-size: 1.5rem; font-weight: 700; {{ $colour ? 'color: '.$colour.';' : '' }}">{{ $figure }}</div>
        </div>
    @endforeach
</div>
<p style="margin-top: 8px;">{{ __('Average from receiving to finishing: :days days (the aim is :aim).', ['days' => $c['average_days'] ?? '–', 'aim' => $c['days']]) }}</p>

@if ($c['total'])
    @php($most = max(1, ...array_values($c['kinds'])))
    <table style="{{ $table }} margin-top: 10px;">
        <tr><th style="{{ $cell }}">{{ __('Kind') }}</th><th style="{{ $cell }} width: 55%;"></th><th style="{{ $number }}">{{ __('Matters') }}</th></tr>
        @foreach ($c['kinds'] as $kind => $count)
            <tr>
                <td style="{{ $cell }}">{{ __(App\Models\Complaint::KINDS[$kind]) }}</td>
                <td style="{{ $cell }}"><div style="height: 10px; width: {{ round($count * 100 / $most) }}%; border-radius: 5px; background: #ea580c;"></div></td>
                <td style="{{ $number }}">{{ $count }}</td>
            </tr>
        @endforeach
    </table>

    <table style="{{ $table }} margin-top: 14px;">
        <tr><th style="{{ $cell }}">{{ __('Month') }}</th><th style="{{ $number }}">{{ __('Matters') }}</th></tr>
        @foreach ($c['monthly'] as $key => $count)
            <tr><td style="{{ $cell }}">{{ $month($key) }}</td><td style="{{ $number }}">{{ $count }}</td></tr>
        @endforeach
    </table>

    @if ($c['groups'])
        <p style="margin: 14px 0 6px; font-weight: 600;">{{ __('Concerning most') }}</p>
        <table style="{{ $table }}">
            @foreach ($c['groups'] as $group => $count)
                <tr><td style="{{ $cell }}">{{ $loop->iteration }}. {{ $group }}</td><td style="{{ $number }}">{{ $count }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($c['follow'])
        <p style="margin: 14px 0 6px; font-weight: 600;">{{ __('To follow up') }}</p>
        <table style="{{ $table }}">
            <tr><th style="{{ $cell }}">{{ __('Code') }}</th><th style="{{ $cell }}">{{ __('Kind') }}</th><th style="{{ $cell }}">{{ __('Received') }}</th><th style="{{ $number }}">{{ __('Days open') }}</th><th style="{{ $cell }}">{{ __('Where it stands') }}</th></tr>
            @foreach ($c['follow'] as $one)
                <tr @if ($one['late']) style="color: #b91c1c;" @endif>
                    <td style="{{ $cell }} font-family: monospace;">{{ $one['code'] }}</td>
                    <td style="{{ $cell }}">{{ __(App\Models\Complaint::KINDS[$one['kind']] ?? $one['kind']) }}</td>
                    <td style="{{ $cell }}">{{ $one['received']->format('d/m/').($one['received']->year + 543) }}</td>
                    <td style="{{ $number }}">{{ $one['days'] }}</td>
                    <td style="{{ $cell }}">{{ __(App\Models\Complaint::STATUSES[$one['status']] ?? $one['status']) }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    <p style="margin-top: 6px; font-size: .85rem; opacity: .75;">{{ __('No name, number or address of whoever sent a matter is in this report: each is its code.') }}</p>
@else
    <p>{{ __('Nothing was sent in during this period.') }}</p>
@endif

<p style="margin-top: 8px; font-size: .85rem; opacity: .75;">{{ __('Pages opened are counted without keeping anything about visitors; they are pages, not people. "Times opened" is counted since each item was published.') }}</p>
