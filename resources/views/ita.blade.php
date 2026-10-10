{{--
    /ita: the documents the hospital publishes for the Integrity and Transparency Assessment, filed by
    fiscal year (newest first) and, within a year, under the headings staff gave them (App\Models\Document).
--}}
@php
    $years = App\Models\Document::shown()->where('section', 'ita')->get()
        ->groupBy(fn ($document) => (int) $document->year)
        ->sortKeysDesc()
        ->map(fn ($documents) => $documents->groupBy(fn ($document) => (string) $document->group)->sortKeysUsing(fn ($a, $b) => strnatcmp($a, $b)));
@endphp
<x-layouts.site :title="__('ITA')" :description="__('Documents published by :name for the Integrity and Transparency Assessment (ITA).', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap max-w-4xl py-10 sm:py-14">
        <p class="eyebrow">ITA</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('Integrity and Transparency Assessment') }}</h1>
        <p class="mt-3 text-muted">{{ __('Documents the hospital publishes for the assessment, by fiscal year.') }}</p>

        @forelse ($years as $year => $groups)
            {{-- The newest year is open; the others open when pressed. --}}
            <details class="card mt-6 p-0" @if ($loop->first) open @endif>
                <summary class="cursor-pointer px-5 py-4 text-xl font-semibold">{{ $year ? __('Fiscal year :year', ['year' => $year]) : __('No year given') }}</summary>
                <div class="border-t border-line px-5 pb-5">
                    @foreach ($groups as $heading => $documents)
                        @if ($heading !== '')
                            <h3 class="mt-5 font-mono text-sm text-syn-function">{{ $heading }}</h3>
                        @endif
                        <ul class="mt-2">
                            @foreach ($documents as $document)
                                <li class="border-b border-line last:border-b-0">
                                    <a href="{{ $document->url() }}" target="_blank" rel="noopener" class="group flex items-center gap-3 py-2.5 no-underline">
                                        <x-heroicon-o-document-arrow-down class="size-5 shrink-0 text-brand" aria-hidden="true" />
                                        <span class="min-w-0 flex-1 break-words text-ink group-hover:text-brand">{{ $document->localTitle() }}</span>
                                        <span class="shrink-0 font-mono text-sm text-muted">{{ $document->kind() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </details>
        @empty
            <p class="card mt-8 text-muted">{{ __('Nothing has been added here yet.') }}</p>
        @endforelse
    </div>
</x-layouts.site>
