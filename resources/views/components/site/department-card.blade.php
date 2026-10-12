{{-- One unit of the hospital in a grid: its round picture, its name, where it is, its hours. The whole card is the link. --}}
@props(['department'])

<a href="{{ route('departments.show', $department) }}" class="card flex flex-col text-ink no-underline">
    <x-site.department-avatar :department="$department" class="size-12 text-xl" />
    <h3 class="mt-4 text-lg">{{ $department->localName() }}</h3>
    @if (filled($department->localHeadline()))
        <p class="mt-1 text-sm text-muted">{{ $department->localHeadline() }}</p>
    @endif
    @if ($department->location)
        <p class="mt-1 text-sm text-muted">{{ $department->location }}</p>
    @endif
    @if ($department->hours)
        <p class="mt-3 line-clamp-2 font-mono text-sm text-syn-string">{{ $department->hours }}</p>
    @endif
</a>
