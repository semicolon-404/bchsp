{{-- /blog/{slug}: one article. --}}
<x-layouts.site :title="$post->localTitle()" :description="$post->localExcerpt()" :image="$post->coverUrl()">
    <article class="wrap max-w-3xl py-10 sm:py-14">
        <p class="text-sm"><a href="{{ route('blog.index') }}" class="font-semibold text-brand underline">&larr; {{ __('All articles') }}</a></p>

        @unless ($post->isPublished())
            {{-- Only staff who are signed in get this far (BlogController::show). --}}
            <p class="mt-6 rounded-lg border border-danger px-4 py-3 text-danger" role="status">{{ __('Not published yet: only staff who are signed in can see this page.') }}</p>
        @endunless

        @if ($post->category)
            <p class="eyebrow mt-6"><a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="text-brand no-underline">{{ $post->category->localName() }}</a></p>
        @endif
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ $post->localTitle() }}</h1>
        <p class="mt-3 font-mono text-sm text-muted"><time datetime="{{ ($post->published_at ?? $post->created_at)->toDateString() }}">{{ $post->publishedOn() }}</time></p>

        @if ($cover = $post->coverUrl())
            <img src="{{ $cover }}" alt="" class="mt-8 w-full rounded-xl border border-line">
        @endif

        <div class="rich mt-8">{!! $post->renderedBody() !!}</div>

        @if ($photos = $post->photos())
            {{-- The album: every photograph whole, each opening at full size. --}}
            <section class="mt-10" aria-label="{{ __('Photographs') }}">
                <h2 class="eyebrow">{{ trans_choice('{1} :count photograph|[2,*] :count photographs', count($photos), ['count' => count($photos)]) }}</h2>
                <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($photos as $photo)
                        <li>
                            <a href="{{ $photo }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-line">
                                <img src="{{ $photo }}" alt="{{ __('Photograph :number of :count', ['number' => $loop->iteration, 'count' => $loop->count]) }}" loading="lazy" class="aspect-[4/3] w-full object-cover">
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>

    @if ($more->isNotEmpty())
        <section class="border-t border-line">
            <div class="wrap py-10">
                <h2 class="eyebrow">{{ __('More to read') }}</h2>
                <div class="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($more as $other)
                        <x-site.article-card :post="$other" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.site>
