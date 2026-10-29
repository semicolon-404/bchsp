<x-filament-widgets::widget>
    {{-- App\Filament\Widgets\RecentActivity: the last things done in the panel, newest first. --}}
    <x-filament::section :heading="__('Recent activity')" icon="heroicon-o-bolt">
        <x-slot name="afterHeader">
            <x-filament::link :href="$this->getAllUrl()">{{ __('See all') }} &rarr;</x-filament::link>
        </x-slot>

        @php($lines = $this->getLines())

        @if ($lines->isEmpty())
            <p class="panel-clear">{{ __('Nothing has been done in the panel yet.') }}</p>
        @else
            <ul class="panel-waiting panel-posts">
                @foreach ($lines as $line)
                    <li>
                        <span class="panel-posts-text">
                            <strong>{{ App\Support\StaffActivity::describe($line) }}</strong>
                            <small>{{ $line->created_at->diffForHumans() }}</small>
                        </span>
                        @if ($line->event === 'login_failed')
                            <x-filament::badge color="danger">{{ __('Failed sign-in') }}</x-filament::badge>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
