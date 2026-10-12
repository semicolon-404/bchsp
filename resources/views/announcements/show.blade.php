{{-- /announcements/{slug}: one announcement, with its files to open or download. --}}
@php($files = $announcement->files())
@php($links = $announcement->documentLinks())
<x-layouts.site :title="$announcement->localTitle()" :image="$announcement->imageUrl()">
    <article class="wrap max-w-3xl py-10 sm:py-14">
        <p class="text-sm"><a href="{{ route('announcements.index') }}" class="font-semibold text-brand underline">&larr; {{ __('All announcements') }}</a></p>

        @if (! $announcement->isPublished())
            {{-- Only staff who are signed in get this far (AnnouncementController::show). --}}
            <p class="mt-6 rounded-lg border border-danger px-4 py-3 text-danger" role="status">{{ __('Not showing to the public right now: only staff who are signed in can see this page.') }}</p>
        @elseif (! $announcement->isCurrent())
            {{-- Kept for the record: a reader should know it is no longer in force. --}}
            <p class="mt-6 rounded-lg border border-line bg-surface px-4 py-3 text-muted" role="status">{{ __('From the archive: this announcement ended on :date.', ['date' => $announcement->ends_at->locale(app()->getLocale())->translatedFormat('j M ').($announcement->ends_at->year + (app()->getLocale() === 'th' ? 543 : 0))]) }}</p>
        @endif

        @if ($announcement->type)
            <p class="eyebrow mt-6"><a href="{{ route('announcements.index', ['type' => $announcement->type->slug]) }}" class="inline-flex items-center gap-2 no-underline" style="color: {{ $announcement->type->colour() }}"><x-site.type-mark :type="$announcement->type" bare />{{ $announcement->type->localName() }}</a></p>
        @endif
        <h1 class="mt-2 text-2xl sm:text-3xl">{{ $announcement->localTitle() }}</h1>
        <p class="mt-3 font-mono text-sm text-muted">
            <time datetime="{{ ($announcement->starts_at ?? $announcement->created_at)->toDateString() }}">{{ $announcement->shownOn() }}</time>
            · <a href="{{ route('announcements.index', array_filter(['type' => $announcement->type?->slug, 'year' => App\Models\Announcement::fiscalYearOf($announcement->starts_at ?? $announcement->created_at)])) }}" class="underline">{{ __('Fiscal year :year', ['year' => App\Models\Announcement::fiscalYearOf($announcement->starts_at ?? $announcement->created_at)]) }}</a>
        </p>

        @if ($announcement->department?->is_active)
            <p class="mt-2 text-sm text-muted">{{ __('Put out by') }} <a href="{{ route('departments.show', $announcement->department) }}" class="font-semibold text-brand underline">{{ $announcement->department->localName() }}</a></p>
        @endif

        @if ($image = $announcement->imageUrl())
            {{-- The announcement as one picture, shown whole whatever its shape; a press opens it at full size. --}}
            <a href="{{ $image }}" target="_blank" rel="noopener" class="mt-8 block">
                <img src="{{ $image }}" alt="{{ $announcement->localTitle() }}" class="mx-auto max-h-[85vh] w-auto max-w-full rounded-xl border border-line">
                <span class="sr-only">{{ __('Open the picture at full size') }}</span>
            </a>
        @endif

        @if ($announcement->hasBody())
            <div class="rich mt-8">{!! $announcement->renderedBody() !!}</div>
        @endif

        @if ($files || $links)
            <section class="mt-8">
                <h2 class="eyebrow">{{ __('Files') }}</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($files as $file)
                        <li>
                            <a href="{{ $file['url'] }}" target="_blank" rel="noopener" class="card flex items-center gap-3 px-4 py-3 text-ink no-underline">
                                <x-heroicon-o-document-arrow-down class="size-6 shrink-0 text-brand" aria-hidden="true" />
                                <span class="min-w-0 flex-1 break-words">{{ $file['name'] }}</span>
                                <span class="shrink-0 font-mono text-sm text-muted">PDF · {{ $file['size'] }}</span>
                            </a>
                        </li>
                    @endforeach
                    {{-- Documents kept elsewhere (Google Drive): the reader is told where a press will take them. --}}
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="card flex items-center gap-3 px-4 py-3 text-ink no-underline">
                                <x-heroicon-o-arrow-top-right-on-square class="size-6 shrink-0 text-brand" aria-hidden="true" />
                                <span class="min-w-0 flex-1 break-words">{{ $link['label'] }}</span>
                                <span class="shrink-0 font-mono text-sm text-muted">{{ $link['host'] }}</span>
                                <span class="sr-only">{{ __('(opens in a new tab)') }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
</x-layouts.site>
