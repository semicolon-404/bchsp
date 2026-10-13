{{--
    "Newer" and "Older" with the page in between: two large buttons are easier to hit than a row of numbers.
    Between them, where the reader is, as a figure and as a bar that fills as they go on.
--}}
@if ($paginator->hasPages())
    <nav class="card flex items-center justify-between gap-3 px-3 py-3" aria-label="{{ __('Pages') }}">
        @if ($paginator->onFirstPage())
            <span class="w-24"></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-line"><x-heroicon-m-chevron-left class="size-4" aria-hidden="true" />{{ __('Newer') }}</a>
        @endif

        <span class="flex min-w-0 flex-1 flex-col items-center gap-1.5">
            <span class="font-mono text-sm text-muted">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            <span class="hidden h-1.5 w-full max-w-40 overflow-hidden rounded-full bg-raised sm:block" aria-hidden="true"><span class="block h-full rounded-full bg-brand" style="width: {{ round($paginator->currentPage() / $paginator->lastPage() * 100) }}%"></span></span>
        </span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-brand">{{ __('Older') }}<x-heroicon-m-chevron-right class="size-4" aria-hidden="true" /></a>
        @else
            <span class="w-24"></span>
        @endif
    </nav>
@endif
