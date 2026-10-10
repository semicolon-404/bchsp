{{-- /announcements: everything inside its dates, pinned ones first, with the types as a filter above. --}}
<x-layouts.site :title="$type ? $type->localName() : __('Announcements')" :description="__('Announcements, procurement notices and job openings from :name.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap py-10 sm:py-14">
        <p class="eyebrow">{{ __('Announcements') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ $type ? $type->localName() : __('Announcements from the hospital') }}</h1>

        @if ($types->isNotEmpty())
            <nav class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('Types') }}">
                <a href="{{ route('announcements.index') }}" @class(['chip', 'is-on' => ! $type]) aria-current="{{ $type ? 'false' : 'page' }}">{{ __('All') }}</a>
                @foreach ($types as $one)
                    <a href="{{ route('announcements.index', ['type' => $one->slug]) }}" @class(['chip', 'is-on' => $type?->is($one)]) aria-current="{{ $type?->is($one) ? 'page' : 'false' }}">{{ $one->localName() }}</a>
                @endforeach
            </nav>
        @endif

        @if ($announcements->isEmpty())
            <p class="card mt-8 text-muted">{{ __('There are no announcements here right now.') }}</p>
        @else
            <ul class="card mt-8 p-0">
                @foreach ($announcements as $announcement)
                    <x-site.announcement-row :announcement="$announcement" />
                @endforeach
            </ul>

            <div class="mt-10">{{ $announcements->links('components.site.pagination') }}</div>
        @endif
    </div>
</x-layouts.site>
