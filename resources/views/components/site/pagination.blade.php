{{-- "Newer" and "Older" with the page in between: two large buttons are easier to hit than a row of numbers. --}}
@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-3" aria-label="{{ __('Pages') }}">
        @if ($paginator->onFirstPage())
            <span></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-line">&larr; {{ __('Newer') }}</a>
        @endif

        <span class="font-mono text-sm text-muted">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-line">{{ __('Older') }} &rarr;</a>
        @else
            <span></span>
        @endif
    </nav>
@endif
