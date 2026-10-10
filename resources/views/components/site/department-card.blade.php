{{-- One unit of the hospital in a grid: its name, where it is, its hours. The whole card is the link. --}}
@props(['department'])

<a href="{{ route('departments.show', $department) }}" class="card flex flex-col text-ink no-underline">
    <span class="flex size-12 items-center justify-center rounded-lg bg-raised text-brand" aria-hidden="true">
        <x-heroicon-o-building-office-2 class="size-6" />
    </span>
    <h3 class="mt-4 text-lg">{{ $department->localName() }}</h3>
    @if ($department->location)
        <p class="mt-1 text-sm text-muted">{{ $department->location }}</p>
    @endif
    @if ($department->hours)
        <p class="mt-3 line-clamp-2 font-mono text-sm text-syn-string">{{ $department->hours }}</p>
    @endif
</a>
