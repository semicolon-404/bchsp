{{--
    /faq: the questions people ask the hospital, each opening onto its answer (App\Models\Faq; written in the panel
    under Hospital > Questions people ask). A <details> each: they open and close without any script, and a search
    engine is told the questions and answers so it can show them itself.
--}}
@php
    $groups = App\Models\Faq::shown()->get()->groupBy(fn ($faq) => (string) $faq->group)->sortKeysUsing(fn ($a, $b) => [$a === '', $a] <=> [$b === '', $b]);
    $all = $groups->flatten();
@endphp
<x-layouts.site :title="__('Questions people ask')" :description="__('Answers to what people most often ask :name.', ['name' => App\Support\SiteSettings::name()])">
    @if ($all->isNotEmpty())
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $all->map(fn ($faq) => ['@type' => 'Question', 'name' => $faq->localQuestion(), 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->localAnswer()]])->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif

    <x-site.page-header :eyebrow="__('Questions people ask')" :title="__('Before you call, the answer may be here')" icon="question-mark-circle" width="max-w-3xl"
                        :lead="__('Press a question to read its answer.')">
        @if ($all->isNotEmpty())
            <div class="flex flex-wrap items-end gap-x-10 gap-y-5">
                <dl>
                    <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($all->count()) }}</dd>
                    <dt class="text-sm text-muted">{{ __('Questions') }}</dt>
                </dl>
                <label class="relative block min-w-0 flex-1 basis-64">
                    <span class="sr-only">{{ __('Find a question') }}</span>
                    <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-muted" aria-hidden="true" />
                    <input type="search" data-filter=".faq-list" placeholder="{{ __('Find a question') }}" class="min-h-12 w-full rounded-lg border border-line bg-surface pl-10 pr-3 text-ink placeholder:text-muted" hidden>
                </label>
            </div>
        @endif
    </x-site.page-header>

    <div class="wrap max-w-3xl py-10 sm:py-14">
        @if ($all->isEmpty())
            <x-site.empty icon="question-mark-circle" />
        @else
            @foreach ($groups as $heading => $faqs)
                <section @class(['mt-10' => ! $loop->first])>
                    @if ($heading !== '')
                        <h2 class="flex items-center gap-2 text-xl"><x-heroicon-o-chat-bubble-left-right class="size-6 text-brand" aria-hidden="true" />{{ $heading }}</h2>
                    @endif
                    <div class="faq-list card mt-3 p-0">
                        @foreach ($faqs as $faq)
                            <details class="group border-b border-line last:border-b-0" id="q-{{ $faq->id }}" data-filter-text="{{ mb_strtolower($faq->localQuestion().' '.$faq->localAnswer()) }}">
                                <summary class="flex cursor-pointer list-none items-start gap-3 px-5 py-4 text-lg font-medium hover:text-brand">
                                    <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-raised font-mono text-sm font-bold text-brand" aria-hidden="true">?</span>
                                    <span class="min-w-0 flex-1">{{ $faq->localQuestion() }}</span>
                                    <x-heroicon-m-chevron-down class="mt-1 size-5 shrink-0 text-brand transition group-open:rotate-180" aria-hidden="true" />
                                </summary>
                                <p class="whitespace-pre-line pb-5 pl-[3.75rem] pr-5 text-muted">{{ $faq->localAnswer() }}</p>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif

        <p class="mt-6 hidden text-muted" data-filter-none>{{ __('No question matches.') }}</p>

        <p class="mt-10 text-muted">{{ __('Not here?') }}
            <a href="{{ route('contact') }}" class="font-semibold text-brand underline">{{ __('Contact us') }}</a>
            · <a href="{{ route('phones') }}" class="font-semibold text-brand underline">{{ __('Phone numbers') }}</a>
        </p>
    </div>
</x-layouts.site>
