<x-filament-widgets::widget>
    {{-- App\Filament\Widgets\NeedsAttention: what is waiting for someone, each line leading to where it is dealt with. --}}
    <x-filament::section :heading="__('Needs attention')" icon="heroicon-o-bell-alert">
        @php($items = $this->getItems())

        @if ($items === [])
            <p class="panel-clear"><x-filament::icon icon="heroicon-o-check-circle" />{{ __('Nothing is waiting. Everything is up to date.') }}</p>
        @else
            <ul class="panel-waiting">
                @foreach ($items as $item)
                    <li>
                        @if ($item['url'])
                            <a href="{{ $item['url'] }}">
                                <x-filament::badge :color="$item['tone']">{{ __(['warning' => 'To do', 'info' => 'Note', 'danger' => 'Problem'][$item['tone']]) }}</x-filament::badge>
                                <span>{{ $item['text'] }}</span>
                                <span class="panel-waiting-go" aria-hidden="true">&rarr;</span>
                            </a>
                        @else
                            <div>
                                <x-filament::badge :color="$item['tone']">{{ __(['warning' => 'To do', 'info' => 'Note', 'danger' => 'Problem'][$item['tone']]) }}</x-filament::badge>
                                <span>{{ $item['text'] }}</span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
