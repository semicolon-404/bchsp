<x-filament-panels::page>
    {{-- App\Filament\Pages\SiteSettings: one form, saved as a whole. --}}
    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 1.5rem">
            <x-filament::button type="submit" icon="heroicon-m-check" wire:loading.attr="disabled">
                {{ __('Save settings') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
