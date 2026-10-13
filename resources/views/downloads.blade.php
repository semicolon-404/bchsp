{{--
    /downloads: the files the hospital offers, under the headings staff gave them; those without one come last.
    The band at the top says how many there are and holds a box that narrows the list as one types
    (resources/js/filter.js); with more than one heading, a row of them leads to each.
--}}
@php
    $groups = App\Models\Document::shown()->get()->groupBy(fn ($document) => (string) $document->group)->sortKeysUsing(fn ($a, $b) => [$a === '', $a] <=> [$b === '', $b]);
    $count = $groups->flatten()->count();
    $heading = fn (string $name) => $name !== '' ? $name : ($groups->count() > 1 ? __('Other documents') : __('Documents'));
    // The kind of a file, as a colour for its mark: read at a glance down a long list.
    $tone = fn (string $kind) => match (strtoupper($kind)) {
        'PDF' => 'var(--color-danger)',
        'DOC', 'DOCX', 'WORD' => 'var(--color-brand)',
        'XLS', 'XLSX', 'EXCEL' => 'var(--color-syn-function)',
        default => 'var(--color-syn-variable)',
    };
@endphp
<x-layouts.site :title="__('Downloads')" :description="__('Forms and documents to download from :name.', ['name' => App\Support\SiteSettings::name()])">
    <x-site.page-header :eyebrow="__('Downloads')" :title="__('Forms and documents')" icon="arrow-down-tray" width="max-w-4xl"
                        :lead="__('Forms to fill in and documents to read, ready to open or print.')">
        @if ($count > 0)
            <div class="flex flex-wrap items-end gap-x-10 gap-y-5">
                <dl class="flex gap-x-10">
                    <div>
                        <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($count) }}</dd>
                        <dt class="text-sm text-muted">{{ __('Documents') }}</dt>
                    </div>
                </dl>
                <label class="relative block min-w-0 flex-1 basis-64 sm:max-w-md">
                    <span class="sr-only">{{ __('Find a document') }}</span>
                    <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-muted" aria-hidden="true" />
                    <input type="search" data-filter=".download-list" placeholder="{{ __('Find a document') }}" class="min-h-12 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-ink placeholder:text-muted" hidden>
                </label>
            </div>
        @endif
    </x-site.page-header>

    <div class="wrap max-w-4xl py-10 sm:py-14">
        @if ($groups->count() > 1)
            <nav class="mb-8 flex flex-wrap gap-2" aria-label="{{ __('Headings') }}">
                @foreach ($groups as $name => $documents)
                    <a href="#group-{{ $loop->index }}" class="chip gap-2">{{ $heading($name) }}<span class="font-mono text-xs text-muted">{{ $documents->count() }}</span></a>
                @endforeach
            </nav>
        @endif

        @forelse ($groups as $name => $documents)
            <section id="group-{{ $loop->index }}" @class(['scroll-mt-24', 'mt-10' => ! $loop->first])>
                <h2 class="flex items-center gap-2 text-xl"><x-heroicon-o-folder-open class="size-6 text-brand" aria-hidden="true" />{{ $heading($name) }}</h2>
                <ul class="download-list card mt-4 p-0">
                    @foreach ($documents as $document)
                        <li class="border-b border-line last:border-b-0" data-filter-text="{{ mb_strtolower($document->localTitle()) }}">
                            <a href="{{ $document->url() }}" target="_blank" rel="noopener" class="group flex items-center gap-4 px-4 py-4 no-underline hover:bg-raised">
                                <span class="flex size-11 shrink-0 flex-col items-center justify-center rounded-lg border border-line bg-page font-mono text-[0.65rem] font-bold leading-none" style="color: {{ $tone($document->kind()) }}" aria-hidden="true">
                                    <x-heroicon-o-document-text class="mb-0.5 size-5" />{{ Str::limit($document->kind(), 4, '') }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block break-words font-medium text-ink group-hover:text-brand">{{ $document->localTitle() }}</span>
                                    <span class="mt-0.5 block font-mono text-xs text-muted">{{ $document->kind() }}@if ($size = $document->size()) · {{ $size }}@endif</span>
                                </span>
                                <span class="hidden shrink-0 items-center gap-1 text-sm font-semibold text-brand sm:inline-flex"><x-heroicon-m-arrow-down-tray class="size-4" aria-hidden="true" />{{ __('Open') }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <x-site.empty icon="arrow-down-tray" />
        @endforelse

        <p class="mt-6 hidden text-muted" data-filter-none>{{ __('No document matches.') }}</p>
    </div>
</x-layouts.site>
