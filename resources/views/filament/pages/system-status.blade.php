<x-filament-panels::page>
    {{-- App\Filament\Pages\SystemStatus: one line for each check, with what to do where something is not right. --}}
    <div style="display: grid; gap: 0.75rem">
        @foreach ($this->getChecks() as $check)
            <x-filament::section>
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem">
                    <x-filament::badge :color="['ok' => 'success', 'warning' => 'warning', 'problem' => 'danger'][$check['state']]">
                        {{ __(['ok' => 'OK', 'warning' => 'Check', 'problem' => 'Problem'][$check['state']]) }}
                    </x-filament::badge>
                    <strong>{{ __($check['name']) }}</strong>
                    <span style="margin-left: auto">{{ $check['found'] }}</span>
                </div>
                @if ($check['advice'])
                    <p style="margin-top: 0.5rem; font-size: 0.875rem; opacity: 0.8">{{ $check['advice'] }}</p>
                @endif
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
