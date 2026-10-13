{{--
    /staff: the services people who work at the hospital reach from here (App\Models\StaffLink), and the way in
    to the panel. Kept out of the public menu and out of search engines; nothing on it is secret, each service
    has its own sign-in, but it is of no use to the public.
--}}
@php
    $groups = App\Models\StaffLink::shown()->get()->filter->safeUrl()->groupBy(fn ($link) => (string) $link->group)->sortKeysUsing(fn ($a, $b) => [$a === '', $a] <=> [$b === '', $b]);
@endphp
<x-layouts.site :title="__('For staff')" :noindex="true">
    <x-site.page-header :eyebrow="__('For staff')" :title="__('Services for staff')" icon="identification" width="max-w-4xl"
                        :lead="__('Each service has its own sign-in. Some open only on the hospital\'s network.')">
        <a href="{{ url('/admin') }}" class="btn btn-brand"><x-heroicon-s-lock-closed class="size-5" aria-hidden="true" />{{ __('Manage this website') }}</a>
    </x-site.page-header>

    <div class="wrap max-w-4xl py-10 sm:py-14">

        @foreach ($groups as $heading => $links)
            <section @class(['mt-10' => ! $loop->first])>
                <h2 class="flex items-center gap-2 text-xl"><x-heroicon-o-squares-2x2 class="size-6 text-brand" aria-hidden="true" />{{ $heading !== '' ? $heading : ($groups->count() > 1 ? __('Other services') : __('Services')) }}</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ $link->safeUrl() }}" target="_blank" rel="noopener noreferrer" class="card flex h-full items-center gap-4 px-4 py-4 text-ink no-underline">
                                <span class="door-mark" aria-hidden="true"><x-heroicon-o-window class="size-5" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium">{{ $link->title }}</span>
                                    @if ($link->note)
                                        <span class="block text-sm text-muted">{{ $link->note }}</span>
                                    @endif
                                </span>
                                <x-heroicon-m-arrow-top-right-on-square class="size-5 shrink-0 text-brand" aria-hidden="true" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
</x-layouts.site>
