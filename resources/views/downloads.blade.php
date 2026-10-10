{{-- /downloads: the files the hospital offers, under the headings staff gave them; those without one come last. --}}
@php
    $groups = App\Models\Document::shown()->where('section', 'downloads')->get()->groupBy(fn ($document) => (string) $document->group)->sortKeysUsing(fn ($a, $b) => [$a === '', $a] <=> [$b === '', $b]);
@endphp
<x-layouts.site :title="__('Downloads')" :description="__('Forms and documents to download from :name.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap max-w-4xl py-10 sm:py-14">
        <p class="eyebrow">{{ __('Downloads') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('Forms and documents') }}</h1>

        @forelse ($groups as $heading => $documents)
            <section class="mt-10">
                <h2 class="text-xl">{{ $heading !== '' ? $heading : ($groups->count() > 1 ? __('Other documents') : __('Documents')) }}</h2>
                <ul class="card mt-4 p-0">
                    @foreach ($documents as $document)
                        <li class="border-b border-line last:border-b-0">
                            <a href="{{ $document->url() }}" target="_blank" rel="noopener" class="group flex items-center gap-3 px-4 py-3 no-underline">
                                <x-heroicon-o-document-arrow-down class="size-6 shrink-0 text-brand" aria-hidden="true" />
                                <span class="min-w-0 flex-1 break-words text-ink group-hover:text-brand">{{ $document->localTitle() }}</span>
                                <span class="shrink-0 font-mono text-sm text-muted">{{ $document->kind() }}@if ($size = $document->size()) · {{ $size }}@endif</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p class="card mt-8 text-muted">{{ __('Nothing has been added here yet.') }}</p>
        @endforelse
    </div>
</x-layouts.site>
