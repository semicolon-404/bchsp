<x-filament-panels::page>
    {{--
        App\Filament\Pages\SystemStatus: at the top, how it stands in a word and how many of each; under it a card
        for each check, those that need someone first, with what to do where something is not right.
        Drawn by resources/css/panel.css ("panel-health").
    --}}
    @php
        $checks = collect($this->getChecks());
        $counts = $checks->countBy('state');
        $worst = $counts->has('problem') ? 'problem' : ($counts->has('warning') ? 'warning' : 'ok');
        $words = ['ok' => 'OK', 'warning' => 'Check', 'problem' => 'Problem'];
        $colours = ['ok' => 'success', 'warning' => 'warning', 'problem' => 'danger'];
        $icons = ['ok' => 'heroicon-o-check-circle', 'warning' => 'heroicon-o-exclamation-triangle', 'problem' => 'heroicon-o-x-circle'];
        $order = ['problem' => 0, 'warning' => 1, 'ok' => 2];
    @endphp

    <section class="panel-health-summary is-{{ $worst }}">
        <span class="panel-health-mark" aria-hidden="true"><x-filament::icon :icon="$icons[$worst]" /></span>
        <div>
            <h2>{{ __(['ok' => 'Everything is working', 'warning' => 'Working, with something to look at', 'problem' => 'Something needs fixing'][$worst]) }}</h2>
            <p>{{ __(':count checks, made just now', ['count' => $checks->count()]) }}</p>
        </div>
        <dl>
            @foreach (['problem', 'warning', 'ok'] as $state)
                <div class="is-{{ $state }}">
                    <dd>{{ $counts->get($state, 0) }}</dd>
                    <dt>{{ __($words[$state]) }}</dt>
                </div>
            @endforeach
        </dl>
    </section>

    <div class="panel-health">
        @foreach ($checks->sortBy(fn (array $check) => $order[$check['state']]) as $check)
            <article class="panel-health-card is-{{ $check['state'] }}">
                <header>
                    <span class="panel-health-mark" aria-hidden="true"><x-filament::icon :icon="$icons[$check['state']]" /></span>
                    <strong>{{ __($check['name']) }}</strong>
                    <x-filament::badge :color="$colours[$check['state']]">{{ __($words[$check['state']]) }}</x-filament::badge>
                </header>
                <p class="panel-health-found">{{ $check['found'] }}</p>
                @if ($check['advice'])
                    <p class="panel-health-advice">{{ $check['advice'] }}</p>
                @endif
            </article>
        @endforeach
    </div>
</x-filament-panels::page>
