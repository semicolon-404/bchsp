<x-filament-panels::page>
    {{-- App\Filament\Pages\Reports: choose a period, read the report; print it or download it from the buttons above. --}}
    {{ $this->form }}

    <x-filament::section :heading="__('Website report').': '.$this->getPeriodName()">
        @include('reports.body', ['summary' => $this->getSummary()])
    </x-filament::section>
</x-filament-panels::page>
