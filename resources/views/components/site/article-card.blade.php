{{-- One article in a list: its cover, category, title, summary and date. The whole card is the link. --}}
@props(['post'])

<a href="{{ route('blog.show', $post) }}" class="card flex flex-col overflow-hidden p-0 text-ink no-underline">
    @if ($cover = $post->coverUrl())
        <img src="{{ $cover }}" alt="" loading="lazy" class="aspect-video w-full object-cover">
    @else
        {{-- No cover: a strip of the editor's look, so that cards in a row stay the same height. --}}
        <div class="flex aspect-video w-full items-center justify-center bg-raised font-mono text-sm text-syn-comment" aria-hidden="true">// {{ $post->category?->localName() ?? __('Article') }}</div>
    @endif

    <div class="flex flex-1 flex-col p-5">
        @if ($post->category)
            <p class="eyebrow">{{ $post->category->localName() }}</p>
        @endif
        <h3 class="mt-1 text-lg">{{ $post->localTitle() }}</h3>
        <p class="mt-2 line-clamp-3 flex-1 text-muted">{{ $post->localExcerpt() }}</p>
        <p class="mt-4 font-mono text-sm text-muted"><time datetime="{{ ($post->published_at ?? $post->created_at)->toDateString() }}">{{ $post->publishedOn() }}</time></p>
    </div>
</a>
