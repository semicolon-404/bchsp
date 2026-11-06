<x-filament-panels::page>
    {{-- The filters, the search, and what can be done to them all. --}}
    <div class="flex flex-wrap items-center gap-2">
        @foreach (['all' => [__('All'), $counts['all']], 'unread' => [__('Unread'), $counts['unread']]] + collect(App\Filament\Pages\Notifications::KINDS)->map(fn ($kind) => [__($kind[0]), null])->all() as $key => [$label, $count])
            <button type="button" wire:click="$set('show', '{{ $key }}')" @class(['nx-chip', 'is-'.$key, 'is-on' => $show === $key])>
                {{ $label }}@if ($count !== null)<span class="nx-count">{{ number_format($count) }}</span>@endif
            </button>
        @endforeach
        <input type="search" wire:model.live.debounce.400ms="q" placeholder="{{ __('Search') }}" class="nx-search ms-auto">
    </div>

    <div class="flex flex-wrap gap-2">
        <x-filament::button color="gray" size="sm" icon="heroicon-m-check" wire:click="markAllRead">{{ __('Mark all as read') }}</x-filament::button>
        <x-filament::button color="gray" size="sm" icon="heroicon-m-trash" wire:click="clearRead" wire:confirm="{{ __('Delete every notification already read?') }}">{{ __('Delete the read ones') }}</x-filament::button>
    </div>

    @forelse ($groups as $day => $ones)
        <section>
            <h2 class="nx-day">{{ $day }}</h2>
            <ul class="nx-list">
                @foreach ($ones as $one)
                    @php($icon = $one->data['icon'] ?? 'heroicon-o-bell')
                    <li>
                        <button type="button" wire:click="open('{{ $one->id }}')" @class(['nx-row', 'is-'.App\Filament\Pages\Notifications::kindOf($one), 'is-unread' => ! $one->read_at])>
                            <span class="nx-icon"><x-filament::icon :icon="$icon" class="size-5" /></span>
                            <span class="min-w-0 flex-1 text-start">
                                <span class="nx-title">{{ $one->data['title'] ?? '' }}</span>
                                @if (filled($one->data['body'] ?? null))
                                    <span class="nx-body">{{ Str::limit(strip_tags($one->data['body']), 160) }}</span>
                                @endif
                            </span>
                            <span class="nx-time">{{ $one->created_at->isToday() ? $one->created_at->format('H:i').' น.' : $one->created_at->locale(app()->getLocale())->translatedFormat('j M H:i') }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <x-filament::empty-state icon="heroicon-o-bell-slash" :heading="__('Nothing here')" :description="__('No notification matches.')" />
    @endforelse

    @if ($more)
        <div class="text-center"><x-filament::button color="gray" wire:click="showMore">{{ __('Show more') }}</x-filament::button></div>
    @endif
</x-filament-panels::page>
