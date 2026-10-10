<x-filament-widgets::widget>
    {{-- App\Filament\Widgets\NeedsAttention: what is waiting for someone, each line leading to where it is dealt with. --}}
    <x-filament::section :heading="__('Needs attention')">
        @php($items = $this->getItems())

        @if ($items === [])
            <p style="opacity: .75">{{ __('Nothing is waiting. Everything is up to date.') }}</p>
        @else
            <ul style="display: grid; gap: 0.5rem">
                @foreach ($items as $item)
                    <li style="display: flex; align-items: center; gap: 0.75rem">
                        <x-filament::badge :color="$item['tone']">{{ __(['warning' => 'To do', 'info' => 'Note', 'danger' => 'Problem'][$item['tone']]) }}</x-filament::badge>
                        @if ($item['url'])
                            <x-filament::link :href="$item['url']">{{ $item['text'] }}</x-filament::link>
                        @else
                            <span>{{ $item['text'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
