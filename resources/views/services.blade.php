{{--
    /services: what a patient can do online, gathered in one place: booking, moving an appointment, the ministry's
    own applications. Each is a link kept in the panel (Hospital > Services for staff, marked "for the public");
    each opens the service itself, in a new tab, with its own sign-in.
--}}
@php
    $groups = App\Models\StaffLink::forThePublic()->get()->filter->safeUrl()->groupBy(fn ($link) => (string) $link->group)->sortKeysUsing(fn ($a, $b) => [$a === '', $a] <=> [$b === '', $b]);
@endphp
<x-layouts.site :title="__('Services online')" :description="__('What you can do online with :name, without coming in.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap max-w-4xl py-10 sm:py-14">
        <p class="eyebrow">{{ __('Services online') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('Do it online, without coming in') }}</h1>
        <p class="mt-3 text-muted">{{ __('Each opens the service itself in a new tab. Some ask you to sign in there.') }}</p>

        @if ($groups->isEmpty())
            <x-site.empty class="mt-8" icon="computer-desktop" />
        @endif

        @foreach ($groups as $heading => $links)
            <section class="mt-10">
                @if ($heading !== '' || $groups->count() > 1)
                    <h2 class="text-xl">{{ $heading !== '' ? $heading : __('Other services') }}</h2>
                @endif
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ $link->safeUrl() }}" target="_blank" rel="noopener noreferrer" class="card flex h-full items-center gap-4 px-4 py-4 text-ink no-underline">
                                <span class="door-mark" aria-hidden="true"><x-heroicon-o-arrow-top-right-on-square class="size-5" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium">{{ $link->title }}</span>
                                    @if ($link->note)
                                        <span class="block text-sm text-muted">{{ $link->note }}</span>
                                    @endif
                                </span>
                                <span class="sr-only">{{ __('(opens in a new tab)') }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
</x-layouts.site>
