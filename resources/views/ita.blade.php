{{--
    /ita and /ita/{year}: the Integrity and Transparency Assessment, a part of the site of its own.
    One fiscal year at a time, chosen from the row of years, and if wished one round of it (?round=q2): the
    six-month round of the second quarter, or the twelve-month one of the fourth. The items stand under the
    headings of the assessment, listed down the side on a wide screen; under each item its parts, the numbered
    documents asked for, each with the evidence the hospital publishes for it (App\Models\ItaItem). The band at
    the top says how far the year has got. Pages an administrator filed under ITA are linked below.
--}}
@php
    $progress = $items->map(fn ($item) => $item->progress($round));
    // Items with nothing due in the chosen round are left out of it.
    $items = $items->filter(fn ($item, $key) => $progress[$key]['of'] > 0);
    $done = $progress->sum('done');
    $of = $progress->sum('of');
    $percent = $of === 0 ? 0 : (int) round($done / $of * 100);
    $groups = $items->groupBy(fn ($item) => (string) $item->category);
    $here = fn (array $with) => route('ita', array_filter(['year' => $year === ($years[0] ?? null) ? null : $year] + $with));
@endphp
<x-layouts.site :title="$year ? __('ITA :year', ['year' => $year]) : 'ITA'" :description="__('Documents published by :name for the Integrity and Transparency Assessment (ITA).', ['name' => App\Support\SiteSettings::name()])">
    <x-site.page-header eyebrow="ITA" :title="__('Integrity and Transparency Assessment')" icon="shield-check" width="max-w-6xl"
                        :lead="__('Documents the hospital publishes for the assessment, by fiscal year.')">
        @if ($of > 0)
            <dl class="flex flex-wrap gap-x-10 gap-y-4">
                <div>
                    <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($items->count()) }}</dd>
                    <dt class="text-sm text-muted">{{ __('Items') }}</dt>
                </div>
                <div>
                    <dd class="font-mono text-3xl font-semibold text-syn-function"><span data-count>{{ number_format($done) }}</span><span class="text-xl text-muted"> / {{ number_format($of) }}</span></dd>
                    <dt class="text-sm text-muted">{{ __('Parts with documents') }}</dt>
                </div>
                <div class="min-w-48 flex-1 sm:max-w-xs">
                    <dd class="font-mono text-3xl font-semibold text-brand">{{ $percent }}%</dd>
                    <dt class="text-sm text-muted">{{ $round ? __(App\Models\ItaItem::ROUNDS[$round]) : __('Published for :year', ['year' => $year]) }}</dt>
                    {{-- The same figure as a bar; the number above says it in words. --}}
                    <dd class="mt-2 h-2 overflow-hidden rounded-full bg-raised" aria-hidden="true"><span class="block h-full rounded-full bg-brand" style="width: {{ $percent }}%"></span></dd>
                </div>
            </dl>
        @endif
    </x-site.page-header>

    <div class="wrap max-w-6xl py-10 sm:py-14">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            @if (count($years) > 1)
                <nav class="flex flex-wrap items-center gap-2" aria-label="{{ __('Fiscal year') }}">
                    <span class="mr-1 text-sm text-muted">{{ __('Fiscal year') }}</span>
                    @foreach ($years as $one)
                        <a href="{{ route('ita', array_filter(['year' => $one === $years[0] ? null : $one, 'round' => $round])) }}" @class(['chip font-mono', 'is-on' => $one === $year]) aria-current="{{ $one === $year ? 'page' : 'false' }}">{{ $one }}</a>
                    @endforeach
                </nav>
            @endif

            @if ($year)
                {{-- One round at a time, for whoever checks what is due at the end of a quarter. --}}
                <nav class="switch ita-rounds" aria-label="{{ __('Round') }}">
                    <a href="{{ $here([]) }}" aria-current="{{ $round === null ? 'true' : 'false' }}">{{ __('All') }}</a>
                    <a href="{{ $here(['round' => 'q2']) }}" aria-current="{{ $round === 'q2' ? 'true' : 'false' }}">{{ __('Six-month round') }}</a>
                    <a href="{{ $here(['round' => 'q4']) }}" aria-current="{{ $round === 'q4' ? 'true' : 'false' }}">{{ __('Twelve-month round') }}</a>
                </nav>
            @endif
        </div>

        @if ($items->isEmpty())
            <x-site.empty icon="shield-check" />
        @else
            <div class="lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10">
                {{-- The headings of the assessment, to jump to; on a phone the page is read straight down. --}}
                @if ($groups->keys()->filter()->isNotEmpty())
                    <aside class="hidden lg:block">
                        <nav class="sticky top-24" aria-label="{{ __('Headings of the assessment') }}">
                            <p class="mb-3 text-sm font-semibold text-muted">{{ __('Fiscal year :year', ['year' => $year]) }}</p>
                            <ol class="ita-toc">
                                @foreach ($groups as $category => $group)
                                    @php
                                        $p = $group->map(fn ($item) => $item->progress($round));
                                    @endphp
                                    <li>
                                        <a href="#{{ 'ita-'.$loop->iteration }}">
                                            <span class="min-w-0 flex-1">{{ $category ?: __('Other items') }}</span>
                                            <span class="font-mono text-xs text-muted">{{ $p->sum('done') }}/{{ $p->sum('of') }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    </aside>
                @endif

                <div class="min-w-0 space-y-12">
                    @foreach ($groups as $category => $group)
                        <section id="{{ 'ita-'.$loop->iteration }}" class="scroll-mt-24">
                            <h2 class="mb-4 text-2xl">{{ $category !== '' ? $category : ($groups->count() > 1 ? __('Other items') : __('Fiscal year :year', ['year' => $year])) }}</h2>

                            <div class="space-y-3">
                                @foreach ($group as $item)
                                    @php
                                        $parts = $item->shownParts($round);
                                        $mine = $item->progress($round);
                                        $state = match (true) { $mine['done'] === $mine['of'] => 'full', $mine['done'] > 0 => 'part', default => 'none' };
                                    @endphp
                                    {{-- Closed until pressed: a year has twenty or more items, and a visitor is after one of them. --}}
                                    <details class="card group p-0" id="{{ Str::slug($item->code) }}">
                                        <summary class="flex cursor-pointer list-none items-center gap-4 px-5 py-4">
                                            {{-- Whether it has its documents, at a glance down the list; the count beside the title says it in words. --}}
                                            <span @class(['ita-state', 'is-'.$state]) aria-hidden="true">
                                                <x-dynamic-component :component="['full' => 'heroicon-m-check', 'part' => 'heroicon-m-ellipsis-horizontal', 'none' => 'heroicon-m-minus'][$state]" class="size-5" />
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block font-mono text-sm font-semibold text-syn-function">{{ $item->code }}</span>
                                                <span class="block">{{ $item->title }}</span>
                                            </span>
                                            <span class="shrink-0 text-right font-mono text-xs text-muted">
                                                @if ($parts)
                                                    {{ __(':done of :of with documents', ['done' => $mine['done'], 'of' => $mine['of']]) }}
                                                @else
                                                    {{ trans_choice('{0} No documents yet|{1} :count document|[2,*] :count documents', count($item->evidence()), ['count' => count($item->evidence())]) }}
                                                @endif
                                            </span>
                                            <x-heroicon-m-chevron-down class="size-5 shrink-0 text-brand transition group-open:rotate-180" aria-hidden="true" />
                                        </summary>
                                        <div class="border-t border-line px-5 py-4">
                                            @if (filled($item->description))
                                                <p class="mb-3 whitespace-pre-line text-muted">{{ $item->description }}</p>
                                            @endif

                                            @if ($parts)
                                                @foreach (collect($parts)->groupBy('round') as $in => $due)
                                                    @if ($round === null && count(array_unique(array_column($parts, 'round'))) > 1)
                                                        <h3 class="ita-round">{{ __(App\Models\ItaItem::ROUNDS[$in]) }}</h3>
                                                    @endif
                                                    <ol class="ita-parts">
                                                        @foreach ($due as $part)
                                                            <li>
                                                                <span @class(['ita-dot', 'is-full' => $part['evidence']]) aria-hidden="true"></span>
                                                                <div class="min-w-0 flex-1">
                                                                    <p class="whitespace-pre-line">{{ $part['title'] }}</p>
                                                                    @if ($part['evidence'])
                                                                        <ul class="mt-2 flex flex-wrap gap-2">
                                                                            @foreach ($part['evidence'] as $file)
                                                                                <li>
                                                                                    <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer" class="ita-file">
                                                                                        <x-dynamic-component :component="$file['elsewhere'] ? 'heroicon-m-arrow-top-right-on-square' : 'heroicon-m-document-arrow-down'" class="size-4 shrink-0" aria-hidden="true" />
                                                                                        <span class="min-w-0 break-words">{{ $file['name'] }}</span>
                                                                                    </a>
                                                                                </li>
                                                                            @endforeach
                                                                        </ul>
                                                                    @else
                                                                        <p class="mt-1 text-sm text-muted">{{ __('Not yet published') }}</p>
                                                                    @endif
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ol>
                                                @endforeach
                                            @endif

                                            @if ($evidence = $item->evidence())
                                                <ul @class(['mt-4' => $parts])>
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
                                            @elseif (! $parts)
                                                <p class="text-sm text-muted">{{ __('No documents have been published under this item yet.') }}</p>
                                            @endif
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
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
