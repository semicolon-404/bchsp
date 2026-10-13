{{--
    /departments: every unit that is shown, in the administrator's order, each as a card that leads to its profile.
    The band at the top says how many there are and how many are open this minute; the box narrows the cards as
    one types (resources/js/filter.js; without scripts every card is simply there).
--}}
@php
    $open = $departments->filter(fn ($department) => $department->sessions->where('is_active', true)->contains(fn ($session) => $session->isOpenNow()))->count();
@endphp
<x-layouts.site :title="__('Departments')" :description="__('The departments of :name: where each is, its phone number and its hours.', ['name' => App\Support\SiteSettings::name()])">
    <x-site.page-header :eyebrow="__('Departments')" :title="__('Departments and services')" icon="building-office-2"
                        :lead="__('Each department has a page of its own: what it does, who works there, when it is open and what it has posted.')">
        @if ($departments->isNotEmpty())
            <div class="flex flex-wrap items-end gap-x-10 gap-y-5">
                <dl class="flex gap-x-10">
                    <div>
                        <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($departments->count()) }}</dd>
                        <dt class="text-sm text-muted">{{ __('Departments') }}</dt>
                    </div>
                    @if ($open > 0)
                        <div>
                            <dd class="font-mono text-3xl font-semibold text-syn-function" data-count>{{ number_format($open) }}</dd>
                            <dt class="text-sm text-muted">{{ __('Open now') }}</dt>
                        </div>
                    @endif
                </dl>
                <label class="relative block min-w-0 flex-1 basis-64 sm:max-w-md">
                    <span class="sr-only">{{ __('Find a department') }}</span>
                    <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-muted" aria-hidden="true" />
                    <input type="search" data-filter="#department-list" placeholder="{{ __('Find a department') }}" class="min-h-12 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-ink placeholder:text-muted" hidden>
                </label>
            </div>
        @endif
    </x-site.page-header>

    <div class="wrap py-10 sm:py-14">
        @if ($departments->isEmpty())
            <x-site.empty icon="building-office-2" />
        @else
            <ul id="department-list" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($departments as $department)
                    <li data-filter-text="{{ mb_strtolower($department->localName().' '.$department->localHeadline().' '.$department->location) }}">
                        <x-site.department-card :department="$department" />
                    </li>
                @endforeach
            </ul>
            <p class="mt-6 hidden text-muted" data-filter-none>{{ __('No department matches.') }}</p>

            <p class="mt-10"><a href="{{ route('phones') }}" class="btn btn-line"><x-heroicon-m-phone class="size-4" aria-hidden="true" />{{ __('Phone numbers of every department') }}</a></p>
        @endif
    </div>
</x-layouts.site>
