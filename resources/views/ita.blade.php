{{--
    /ita and /ita/{year}: the Integrity and Transparency Assessment, a part of the site of its own.
    One fiscal year at a time, chosen from the row of years; under each numbered item, the evidence the
    hospital publishes for it (App\Models\ItaItem). The band at the top says how far the year has got: how many
    items have their documents. Pages an administrator filed under ITA are linked below.
--}}
@php
    $done = $items->filter(fn ($item) => count($item->evidence()) > 0)->count();
    $percent = $items->isEmpty() ? 0 : (int) round($done / $items->count() * 100);
@endphp
<x-layouts.site :title="$year ? __('ITA :year', ['year' => $year]) : 'ITA'" :description="__('Documents published by :name for the Integrity and Transparency Assessment (ITA).', ['name' => App\Support\SiteSettings::name()])">
    <x-site.page-header eyebrow="ITA" :title="__('Integrity and Transparency Assessment')" icon="shield-check" width="max-w-4xl"
                        :lead="__('Documents the hospital publishes for the assessment, by fiscal year.')">
        @if ($items->isNotEmpty())
            <dl class="flex flex-wrap gap-x-10 gap-y-4">
                <div>
                    <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($items->count()) }}</dd>
                    <dt class="text-sm text-muted">{{ __('Items') }}</dt>
                </div>
                <div>
                    <dd class="font-mono text-3xl font-semibold text-syn-function" data-count>{{ number_format($done) }}</dd>
                    <dt class="text-sm text-muted">{{ __('With documents') }}</dt>
                </div>
                <div class="min-w-48 flex-1 sm:max-w-xs">
                    <dd class="font-mono text-3xl font-semibold text-brand">{{ $percent }}%</dd>
                    <dt class="text-sm text-muted">{{ __('Published for :year', ['year' => $year]) }}</dt>
                    {{-- The same figure as a bar; the number above says it in words. --}}
                    <dd class="mt-2 h-2 overflow-hidden rounded-full bg-raised" aria-hidden="true"><span class="block h-full rounded-full bg-brand" style="width: {{ $percent }}%"></span></dd>
                </div>
            </dl>
        @endif
    </x-site.page-header>

    <div class="wrap max-w-4xl py-10 sm:py-14">
        @if (count($years) > 1)
            <nav class="mb-8 flex flex-wrap items-center gap-2" aria-label="{{ __('Fiscal year') }}">
                <span class="mr-1 text-sm text-muted">{{ __('Fiscal year') }}</span>
                @foreach ($years as $one)
                    <a href="{{ route('ita', $one === $years[0] ? [] : ['year' => $one]) }}" @class(['chip font-mono', 'is-on' => $one === $year]) aria-current="{{ $one === $year ? 'page' : 'false' }}">{{ $one }}</a>
                @endforeach
            </nav>
        @endif

        @if ($items->isEmpty())
            <x-site.empty icon="shield-check" />
        @else
            <h2 class="text-2xl">{{ __('Fiscal year :year', ['year' => $year]) }}</h2>

            <div class="mt-4 space-y-3">
                @foreach ($items as $item)
                    @php($evidence = $item->evidence())
                    {{-- Closed until pressed: a year has twenty or more items, and a visitor is after one of them. --}}
                    <details class="card group p-0" id="{{ Str::slug($item->code) }}">
                        <summary class="flex cursor-pointer list-none items-center gap-4 px-5 py-4">
                            {{-- Whether it has its documents, at a glance down the list; the count beside the title says it in words. --}}
                            <span @class(['flex size-9 shrink-0 items-center justify-center rounded-full', 'bg-syn-function/15 text-syn-function' => $evidence, 'bg-raised text-muted' => ! $evidence]) aria-hidden="true">
                                <x-dynamic-component :component="$evidence ? 'heroicon-m-check' : 'heroicon-m-minus'" class="size-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-mono text-sm font-semibold text-syn-function">{{ $item->code }}</span>
                                <span class="block">{{ $item->title }}</span>
                            </span>
                            <span class="shrink-0 font-mono text-xs text-muted">{{ trans_choice('{0} No documents yet|{1} :count document|[2,*] :count documents', count($evidence), ['count' => count($evidence)]) }}</span>
                            <x-heroicon-m-chevron-down class="size-5 shrink-0 text-brand transition group-open:rotate-180" aria-hidden="true" />
                        </summary>
                        <div class="border-t border-line px-5 py-4">
                            @if (filled($item->description))
                                <p class="mb-3 whitespace-pre-line text-muted">{{ $item->description }}</p>
                            @endif
                            @if ($evidence)
                                <ul>
                                    @foreach ($evidence as $file)
                                        <li class="border-b border-line last:border-b-0">
                                            <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer" class="group/file flex items-center gap-3 py-3 no-underline">
                                                <x-dynamic-component :component="$file['elsewhere'] ? 'heroicon-o-arrow-top-right-on-square' : 'heroicon-o-document-arrow-down'" class="size-5 shrink-0 text-brand" aria-hidden="true" />
                                                <span class="min-w-0 flex-1 break-words text-ink group-hover/file:text-brand">{{ $file['name'] }}</span>
                                                <span class="shrink-0 font-mono text-sm text-muted">{{ $file['kind'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-muted">{{ __('No documents have been published under this item yet.') }}</p>
                            @endif
                        </div>
                    </details>
                @endforeach
            </div>
        @endif

        @if ($pages->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-2xl">{{ __('Related pages') }}</h2>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($pages as $page)
                        <li>
                            <a href="{{ route('pages.show', $page) }}" class="card flex h-full items-center gap-4 text-ink no-underline">
                                <span class="door-mark" aria-hidden="true"><x-heroicon-o-document-text class="size-5" /></span>
                                <span class="min-w-0 flex-1 font-medium">{{ $page->localTitle() }}</span>
                                <x-heroicon-m-chevron-right class="size-5 shrink-0 text-brand" aria-hidden="true" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.site>
