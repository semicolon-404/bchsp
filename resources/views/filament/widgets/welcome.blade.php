<x-filament-widgets::widget>
    {{-- App\Filament\Widgets\Welcome: the greeting and the things most people come to the panel to do. Drawn by resources/css/panel.css. --}}
    <section class="panel-welcome" aria-labelledby="panel-welcome-title">
        <div>
            <p class="panel-welcome-eyebrow"><span class="panel-welcome-dot" aria-hidden="true"></span>{{ config('app.name') }}</p>
            <h2 id="panel-welcome-title" class="panel-welcome-title">{{ $this->getGreeting() }}</h2>
            <p class="panel-welcome-lead">{{ __('What would you like to do today?') }}</p>
            <p class="panel-welcome-who">
                <span class="panel-pill"><x-filament::icon icon="heroicon-m-user-circle" />{{ $this->getRole() }}</span>
                @if ($department = $this->getDepartment())
                    <span class="panel-pill"><x-filament::icon icon="heroicon-m-building-office-2" />{{ $department }}</span>
                @endif
                <span class="panel-pill"><x-filament::icon icon="heroicon-m-calendar-days" />{{ now()->locale(app()->getLocale())->isoFormat('dddd D MMMM') }}</span>
            </p>
        </div>

        <nav class="panel-doors" aria-label="{{ __('Shortcuts') }}">
            @foreach ($this->getDoors() as $door)
                <a href="{{ $door['url'] }}" @class(['panel-door', 'is-first' => $loop->first]) @if ($door['new']) target="_blank" rel="noopener" @endif>
                    <span class="panel-door-mark" aria-hidden="true"><x-filament::icon :icon="$door['icon']" /></span>
                    <span>{{ $door['label'] }}<small>{{ $door['note'] }}</small></span>
                    @if ($door['count'] > 0)
                        <span class="panel-door-count">{{ number_format($door['count']) }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </section>
</x-filament-widgets::widget>
