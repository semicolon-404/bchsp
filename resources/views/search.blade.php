{{--
    /search: one box, and what was found, kind by kind (App\Http\Controllers\SearchController). With nothing typed,
    or nothing found, the places people most often look for are offered instead of an empty page.
--}}
@php
    $marks = ['Announcements' => 'megaphone', 'Pages' => 'document-text', 'Departments' => 'building-office-2'];
    $short = $term !== '' && mb_strlen($term) < App\Http\Controllers\SearchController::MIN;
@endphp
<x-layouts.site :title="__('Search')" :noindex="true">
    <x-site.page-header :eyebrow="__('Search')" :title="__('Search this website')" icon="magnifying-glass" width="max-w-3xl">
        {{-- Sent with GET: the address can be shared, and nothing typed here is kept by the site. --}}
        <form action="{{ route('search') }}" method="get" role="search" class="flex gap-2">
            <label class="relative block min-w-0 flex-1">
                <span class="sr-only">{{ __('Words to look for') }}</span>
                <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-muted" aria-hidden="true" />
                <input id="q" type="search" name="q" value="{{ $term }}" maxlength="80" required minlength="{{ App\Http\Controllers\SearchController::MIN }}" autofocus
                       placeholder="{{ __('e.g. vaccine, procurement, dental') }}"
                       class="min-h-14 w-full rounded-xl border border-line bg-surface pl-12 pr-4 text-lg text-ink placeholder:text-muted">
            </label>
            <button class="btn btn-brand min-h-14 px-6"><x-heroicon-m-magnifying-glass class="size-5 sm:hidden" aria-hidden="true" /><span class="max-sm:sr-only">{{ __('Search') }}</span></button>
        </form>

        @if ($term !== '')
            <p class="mt-5 text-lg text-muted" role="status">
                @if ($short)
                    {{ __('Type at least :count characters.', ['count' => App\Http\Controllers\SearchController::MIN]) }}
                @else
                    {{ trans_choice('{0} Nothing was found for ":term".|{1} :count result for ":term"|[2,*] :count results for ":term"', $count, ['count' => $count, 'term' => $term]) }}
                @endif
            </p>
        @endif
    </x-site.page-header>

    <div class="wrap max-w-3xl py-10 sm:py-14">
        @foreach ($results as $kind => $found)
            <section @class(['mt-10' => ! $loop->first])>
                <h2 class="flex items-center gap-2 text-xl">
                    <x-dynamic-component :component="'heroicon-o-'.($marks[$kind] ?? 'document-text')" class="size-6 text-brand" aria-hidden="true" />{{ __($kind) }}
                    <span class="font-mono text-sm font-normal text-muted">{{ count($found) }}</span>
                </h2>
                <ul class="card mt-3 p-0">
                    @foreach ($found as $result)
                        <li class="border-b border-line last:border-b-0">
                            <a href="{{ $result['url'] }}" class="group flex items-center gap-4 px-4 py-4 no-underline hover:bg-raised">
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium text-ink group-hover:text-brand">{{ $result['title'] }}</span>
                                    @if ($result['note'])
                                        <span class="mt-0.5 block font-mono text-sm text-muted">{{ $result['note'] }}</span>
                                    @endif
                                </span>
                                <x-heroicon-m-chevron-right class="size-5 shrink-0 text-brand" aria-hidden="true" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        {{-- Nothing to show: the places most people are looking for. --}}
        @if ($results === [])
            <section>
                <h2 class="text-xl">{{ __('Or go straight to') }}</h2>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        [route('announcements.index'), 'Announcements', 'megaphone'],
                        [route('schedule'), 'Clinic schedule', 'calendar-days'],
                        [route('departments.index'), 'Departments', 'building-office-2'],
                        [route('phones'), 'Phone numbers', 'phone'],
                        [route('downloads'), 'Downloads', 'arrow-down-tray'],
                        [route('contact'), 'Contact us', 'map-pin'],
                    ] as [$address, $label, $mark])
                        <li>
                            <a href="{{ $address }}" class="card flex h-full items-center gap-4 text-ink no-underline">
                                <span class="door-mark" aria-hidden="true"><x-dynamic-component :component="'heroicon-o-'.$mark" class="size-5" /></span>
                                <span class="min-w-0 flex-1 font-medium">{{ __($label) }}</span>
                                <x-heroicon-m-chevron-right class="size-5 shrink-0 text-brand" aria-hidden="true" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.site>
