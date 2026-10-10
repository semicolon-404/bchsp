{{-- /p/{slug}: a page of text written in the panel (App\Models\SitePage). --}}
<x-layouts.site :title="$page->localTitle()" :description="$page->summary()">
    <article class="wrap max-w-3xl py-10 sm:py-14">
        @unless ($page->is_published)
            {{-- Only an administrator gets this far (SitePageController). --}}
            <p class="mb-6 rounded-lg border border-danger px-4 py-3 text-danger" role="status">{{ __('Not published yet: only administrators can see this page.') }}</p>
        @endunless

        <h1 class="text-3xl sm:text-4xl">{{ $page->localTitle() }}</h1>
        <div class="rich mt-8">{!! $page->renderedBody() !!}</div>
    </article>
</x-layouts.site>
