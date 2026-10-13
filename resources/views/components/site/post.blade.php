{{--
    One announcement as a post in a department's timeline (departments/show): who put it out and when, its
    title, the start of its text, and its picture whole. The title and the picture lead to the announcement.
    In the feed of all departments (posts.blade.php, "linked") the department's name leads to its own page, and the
    post says how often it has been read.
--}}
@props(['announcement', 'department', 'linked' => false])
@php
    $text = Str::limit(trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($announcement->inLanguage('body'))))), 220);
    $photos = count($announcement->photos());
@endphp

<li class="card p-0">
    <div class="flex items-center gap-3 px-4 pt-4">
        <x-site.department-avatar :department="$department" class="size-10 text-base" />
        <div class="min-w-0">
            <p class="truncate font-medium">
                @if ($linked)
                    <a href="{{ route('departments.show', $department) }}" class="text-ink no-underline hover:text-brand">{{ $department->localName() }}</a>
                @else
                    {{ $department->localName() }}
                @endif
            </p>
            <p class="flex flex-wrap items-center gap-x-2 font-mono text-xs text-muted">
                @if ($announcement->isNew())
                    <span class="inline-flex items-center rounded bg-brand px-1.5 font-sans text-xs font-semibold text-on-brand">{{ __('New') }}</span>
                @endif
                @if ($announcement->pinned_in_department)
                    <span class="inline-flex items-center gap-1 rounded border border-brand px-1.5 font-sans font-semibold text-brand"><x-heroicon-s-bookmark class="size-3" aria-hidden="true" />{{ __('Pinned') }}</span>
                @endif
                <time datetime="{{ ($announcement->starts_at ?? $announcement->created_at)->toDateString() }}">{{ $announcement->shownOn() }}</time>
                @if ($announcement->type)
                    <span style="color: {{ $announcement->type->colour() }}">{{ $announcement->type->localName() }}</span>
                @endif
            </p>
        </div>
    </div>

    <div class="px-4 py-3">
        <h3 class="text-lg"><a href="{{ route('announcements.show', $announcement) }}" class="text-ink no-underline hover:text-brand">{{ $announcement->localTitle() }}</a></h3>
        @if ($text !== '')
            <p class="mt-1 text-muted">{{ $text }}</p>
        @endif
    </div>

    @if ($image = $announcement->imageUrl())
        {{-- Whole, whatever its shape, up to most of a screen: a poster is read, not glimpsed. --}}
        <a href="{{ route('announcements.show', $announcement) }}" class="block border-t border-line bg-raised">
            <img src="{{ $image }}" alt="" loading="lazy" class="mx-auto max-h-[36rem] w-auto max-w-full">
        </a>
    @endif

    @if ($photos || $announcement->hasDocuments() || $linked)
        <p class="flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-line px-4 py-2 text-sm text-muted">
            @if ($photos)
                <span class="inline-flex items-center gap-1"><x-heroicon-m-photo class="size-4" aria-hidden="true" />{{ trans_choice('{1} :count photograph|[2,*] :count photographs', $photos, ['count' => $photos]) }}</span>
            @endif
            @if ($announcement->hasDocuments())
                <span class="inline-flex items-center gap-1"><x-heroicon-m-paper-clip class="size-4" aria-hidden="true" />{{ __('Has files attached') }}</span>
            @endif
            @if ($linked)
                @if ($announcement->views > 0)
                    <span class="inline-flex items-center gap-1"><x-heroicon-m-eye class="size-4" aria-hidden="true" />{{ trans_choice('{1} opened :count time|[2,*] opened :count times', $announcement->views, ['count' => number_format($announcement->views)]) }}</span>
                @endif
                <a href="{{ route('announcements.show', $announcement) }}" class="ml-auto inline-flex items-center gap-1 font-semibold text-brand no-underline">{{ __('Read') }}<x-heroicon-m-arrow-right class="size-4" aria-hidden="true" /></a>
            @endif
        </p>
    @endif
</li>
