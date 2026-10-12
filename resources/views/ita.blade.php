{{--
    /ita and /ita/{year}: the Integrity and Transparency Assessment, a part of the site of its own.
    One fiscal year at a time, chosen from the row of years; under each numbered item, the evidence the
    hospital publishes for it (App\Models\ItaItem). Pages an administrator filed under ITA are linked below.
--}}
<x-layouts.site :title="$year ? __('ITA :year', ['year' => $year]) : 'ITA'" :description="__('Documents published by :name for the Integrity and Transparency Assessment (ITA).', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap max-w-4xl py-10 sm:py-14">
        <p class="eyebrow">ITA</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('Integrity and Transparency Assessment') }}</h1>
        <p class="mt-3 text-muted">{{ __('Documents the hospital publishes for the assessment, by fiscal year.') }}</p>

        @if (count($years) > 1)
            <nav class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('Fiscal year') }}">
                @foreach ($years as $one)
                    <a href="{{ route('ita', $one === $years[0] ? [] : ['year' => $one]) }}" @class(['chip', 'is-on' => $one === $year]) aria-current="{{ $one === $year ? 'page' : 'false' }}">{{ $one }}</a>
                @endforeach
            </nav>
        @endif

        @if ($items->isEmpty())
            <x-site.empty class="mt-8" icon="shield-check" />
        @else
            <h2 class="mt-10 text-xl">{{ __('Fiscal year :year', ['year' => $year]) }}</h2>

            <div class="mt-4 space-y-3">
                @foreach ($items as $item)
                    @php($evidence = $item->evidence())
                    {{-- Closed until pressed: a year has twenty or more items, and a visitor is after one of them. --}}
                    <details class="card p-0" id="{{ Str::slug($item->code) }}">
                        <summary class="flex cursor-pointer items-baseline gap-3 px-5 py-4">
                            <span class="shrink-0 font-mono text-sm font-semibold text-syn-function">{{ $item->code }}</span>
                            <span class="min-w-0 flex-1">{{ $item->title }}</span>
                            <span class="shrink-0 font-mono text-xs text-muted">{{ trans_choice('{0} No documents yet|{1} :count document|[2,*] :count documents', count($evidence), ['count' => count($evidence)]) }}</span>
                        </summary>
                        <div class="border-t border-line px-5 py-4">
                            @if (filled($item->description))
                                <p class="mb-3 whitespace-pre-line text-muted">{{ $item->description }}</p>
                            @endif
                            @if ($evidence)
                                <ul>
                                    @foreach ($evidence as $file)
                                        <li class="border-b border-line last:border-b-0">
                                            <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer" class="group flex items-center gap-3 py-2.5 no-underline">
                                                <x-dynamic-component :component="$file['elsewhere'] ? 'heroicon-o-arrow-top-right-on-square' : 'heroicon-o-document-arrow-down'" class="size-5 shrink-0 text-brand" aria-hidden="true" />
                                                <span class="min-w-0 flex-1 break-words text-ink group-hover:text-brand">{{ $file['name'] }}</span>
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
                <h2 class="eyebrow">{{ __('Related pages') }}</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($pages as $page)
                        <li><a href="{{ route('pages.show', $page) }}" class="card block px-4 py-3 text-ink no-underline">{{ $page->localTitle() }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.site>
