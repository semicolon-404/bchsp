<x-filament-widgets::widget>
    {{-- App\Filament\Widgets\LatestPosts: the posts last worked on, each leading to where it is edited. --}}
    <x-filament::section :heading="__('Last worked on')" icon="heroicon-o-clock">
        <x-slot name="afterHeader">
            <x-filament::link :href="$this->getAllUrl()">{{ __('See all') }} &rarr;</x-filament::link>
        </x-slot>

        @php($posts = $this->getPosts())

        @if ($posts->isEmpty())
            <p class="panel-clear">{{ __('Nothing has been posted yet.') }}</p>
        @else
            <ul class="panel-waiting panel-posts">
                @foreach ($posts as $post)
                    <li>
                        <a href="{{ App\Filament\Resources\Announcements\AnnouncementResource::getUrl('edit', ['record' => $post]) }}">
                            <span class="panel-posts-text">
                                <strong>{{ $post->title }}</strong>
                                <small>{{ collect([$post->type?->name, $post->department?->name, $post->updated_at->diffForHumans()])->filter()->implode(' · ') }}</small>
                            </span>
                            <x-filament::badge :color="$this->colorOf($post)">{{ __($post->state()) }}</x-filament::badge>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
