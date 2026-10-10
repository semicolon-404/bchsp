{{-- /blog: every article that is out, newest first, with the categories as a filter above. --}}
<x-layouts.site :title="$category ? $category->localName() : __('Articles')" :description="__('Health knowledge and news of activities from :name.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap py-10 sm:py-14">
        <p class="eyebrow">{{ __('Blog') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ $category ? $category->localName() : __('Health knowledge and news') }}</h1>

        @if ($categories->isNotEmpty())
            <nav class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('Categories') }}">
                <a href="{{ route('blog.index') }}" @class(['chip', 'is-on' => ! $category]) @if (! $category) aria-current="page" @endif>{{ __('All') }}</a>
                @foreach ($categories as $one)
                    <a href="{{ route('blog.index', ['category' => $one->slug]) }}" @class(['chip', 'is-on' => $category?->is($one)]) @if ($category?->is($one)) aria-current="page" @endif>{{ $one->localName() }}</a>
                @endforeach
            </nav>
        @endif

        @if ($posts->isEmpty())
            <p class="card mt-8 text-muted">{{ __('There are no articles here yet.') }}</p>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-site.article-card :post="$post" />
                @endforeach
            </div>

            <div class="mt-10">{{ $posts->links('components.site.pagination') }}</div>
        @endif
    </div>
</x-layouts.site>
