{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
{{-- /feed: the newest announcements as RSS 2.0 (SeoController::feed). --}}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ $name }}</title>
        <link>{{ route('home') }}</link>
        <description>{{ __('News, announcements and services of :name.', ['name' => $name]) }}</description>
        <language>{{ app()->getLocale() }}</language>
        <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml" />
        @if ($items->isNotEmpty())
            <lastBuildDate>{{ $items->first()->starts_at->toRfc2822String() }}</lastBuildDate>
        @endif
        @foreach ($items as $item)
            <item>
                <title>{{ $item->localTitle() }}</title>
                <link>{{ route('announcements.show', $item) }}</link>
                <guid isPermaLink="true">{{ route('announcements.show', $item) }}</guid>
                <pubDate>{{ $item->starts_at->toRfc2822String() }}</pubDate>
                @if ($item->type)
                    <category>{{ $item->type->localName() }}</category>
                @endif
                <description>{{ Str::limit(trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($item->inLanguage('body'))))), 300) }}</description>
            </item>
        @endforeach
    </channel>
</rss>
