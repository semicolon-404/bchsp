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

    <div class="wrap max-w-3xl py-10 sm:py-14">
        <p class="eyebrow">{{ __('Questions people ask') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('Before you call, the answer may be here') }}</h1>

        @if ($all->isEmpty())
            <x-site.empty class="mt-8" icon="question-mark-circle" />
        @else
            @foreach ($groups as $heading => $faqs)
                <section class="mt-8">
                    @if ($heading !== '')
                        <h2 class="text-xl">{{ $heading }}</h2>
                    @endif
                    <div class="card mt-3 p-0">
                        @foreach ($faqs as $faq)
                            <details class="group border-b border-line last:border-b-0" id="q-{{ $faq->id }}">
                                <summary class="flex cursor-pointer list-none items-start gap-3 px-5 py-4 font-medium hover:text-brand">
                                    <span class="min-w-0 flex-1">{{ $faq->localQuestion() }}</span>
                                    <x-heroicon-m-chevron-down class="mt-1 size-5 shrink-0 text-brand transition group-open:rotate-180" aria-hidden="true" />
                                </summary>
                                <p class="whitespace-pre-line px-5 pb-5 text-muted">{{ $faq->localAnswer() }}</p>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif

        <p class="mt-10 text-muted">{{ __('Not here?') }}
            <a href="{{ route('contact') }}" class="font-semibold text-brand underline">{{ __('Contact us') }}</a>
            · <a href="{{ route('phones') }}" class="font-semibold text-brand underline">{{ __('Phone numbers') }}</a>
        </p>
    </div>
</x-layouts.site>
