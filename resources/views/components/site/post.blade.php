{{--
    One announcement as a post in a department's timeline (departments/show): who put it out and when, its
    title, the start of its text, and its picture whole. The title and the picture lead to the announcement.
--}}
@props(['announcement', 'department'])
@php
    $text = Str::limit(trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($announcement->inLanguage('body'))))), 220);
    $photos = count($announcement->photos());
@endphp

<li class="card p-0">
    <div class="flex items-center gap-3 px-4 pt-4">
        <x-site.department-avatar :department="$department" class="size-10 text-base" />
        <div class="min-w-0">
            <p class="truncate font-medium">{{ $department->localName() }}</p>
            <p class="flex flex-wrap items-center gap-x-2 font-mono text-xs text-muted">
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

    @if ($photos || $announcement->hasDocuments())
        <p class="flex flex-wrap gap-x-4 gap-y-1 border-t border-line px-4 py-2 text-sm text-muted">
            @if ($photos)
                <span class="inline-flex items-center gap-1"><x-heroicon-m-photo class="size-4" aria-hidden="true" />{{ trans_choice('{1} :count photograph|[2,*] :count photographs', $photos, ['count' => $photos]) }}</span>
            @endif
            @if ($announcement->hasDocuments())
                <span class="inline-flex items-center gap-1"><x-heroicon-m-paper-clip class="size-4" aria-hidden="true" />{{ __('Has files attached') }}</span>
            @endif
        </p>
    @endif
</li>
