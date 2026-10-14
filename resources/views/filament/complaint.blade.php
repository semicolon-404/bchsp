{{--
    A matter as it was sent, in the panel (ComplaintResource::form, "matter"): to read and not to change. What
    it is about, what happened, who sent it (or that they chose not to say), and what they attached, each file
    opened through the panel alone (ComplaintController::file).
--}}
@php
    use App\Models\Complaint;

    $record = $getRecord();
    $row = 'display: grid; grid-template-columns: 9rem 1fr; gap: 0.25rem 1rem; padding: 0.4rem 0; border-bottom: 1px solid color-mix(in oklab, var(--gray-500) 14%, transparent);';
    $name = 'font-size: 0.8125rem; opacity: 0.7;';
    $who = array_filter([
        'Full name' => $record->name, 'Phone number' => $record->phone, 'Email' => $record->email, 'LINE ID' => $record->line_id, 'Address to write back to' => $record->address,
    ]);
@endphp
<div style="display: grid; gap: 1.25rem;">
    <div>
        <p style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
            <x-filament::badge :color="App\Filament\Resources\Complaints\ComplaintResource::colourOf($record->status)">{{ __(Complaint::STATUSES[$record->status] ?? $record->status) }}</x-filament::badge>
            <x-filament::badge color="gray">{{ __(Complaint::KINDS[$record->kind] ?? 'Anything else') }}</x-filament::badge>
            @if ($record->isOverdue())
                <x-filament::badge color="danger">{{ __('Past its :count days', ['count' => App\Support\SiteSettings::complaintDays()]) }}</x-filament::badge>
            @endif
        </p>
        <h3 style="margin-top: 0.6rem; font-size: 1.2rem; font-weight: 700; line-height: 1.4;">{{ $record->title }}</h3>
        <p style="margin-top: 0.5rem; white-space: pre-line; line-height: 1.7;">{{ $record->detail }}</p>
    </div>

    <dl>
        <div style="{{ $row }}"><dt style="{{ $name }}">{{ __('Sent in') }}</dt><dd>{{ $record->created_at->locale(app()->getLocale())->translatedFormat('j F Y H:i') }} · {{ __('due by :date', ['date' => $record->dueOn()->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</dd></div>
        @if ($record->happened_on)
            <div style="{{ $row }}"><dt style="{{ $name }}">{{ __('Day it happened') }}</dt><dd>{{ $record->happened_on->locale(app()->getLocale())->translatedFormat('j F Y') }}</dd></div>
        @endif
        @if (filled($record->place))
            <div style="{{ $row }}"><dt style="{{ $name }}">{{ __('Where it happened') }}</dt><dd>{{ $record->place }}</dd></div>
        @endif
        @if ($record->workGroup)
            <div style="{{ $row }}"><dt style="{{ $name }}">{{ __('Group of work it is about') }}</dt><dd>{{ $record->workGroup->name }}</dd></div>
        @endif
        @if ($record->handler)
            <div style="{{ $row }}"><dt style="{{ $name }}">{{ __('Last handled by') }}</dt><dd>{{ $record->handler->name }}</dd></div>
        @endif
    </dl>

    <x-filament::section :heading="__('Who sent it')" icon="heroicon-o-user" compact>
        @if ($record->is_anonymous || ! $who)
            <p style="opacity: 0.8;">{{ __('The sender chose to stay unnamed. They follow the matter with the tracking number and code.') }}</p>
        @else
            <dl>
                @foreach ($who as $label => $value)
                    <div style="{{ $row }}"><dt style="{{ $name }}">{{ __($label) }}</dt><dd style="word-break: break-word;">{{ $value }}</dd></div>
                @endforeach
            </dl>
            <p style="margin-top: 0.6rem; font-size: 0.8125rem; opacity: 0.7;">{{ __('Personal: use it only to deal with this matter, and do not pass it on.') }}</p>
        @endif
    </x-filament::section>

    @if ($files = $record->files())
        <x-filament::section :heading="__('Proof')" icon="heroicon-o-paper-clip" compact>
            <ul style="display: grid; gap: 0.4rem;">
                @foreach ($files as $index => $file)
                    <li><x-filament::link :href="route('complaint.file', [$record, $index])" target="_blank" icon="heroicon-m-arrow-down-tray">{{ $file['name'] }}</x-filament::link></li>
                @endforeach
            </ul>
        </x-filament::section>
    @endif
</div>
