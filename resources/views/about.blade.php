{{--
    /about: everything about the hospital, as one page of links: the pages an administrator filed under
    "About us" (System > Pages), then the departments and the contact page. The top bar's "About us" leads here.
--}}
@php
    $about = collect(App\Support\Menus::items('all'))->firstWhere('key', 'about');
    // A mark for each line: the site's own places by where they lead, a page of text by a word in its name.
    $mark = fn (array $child) => match (true) {
        $child['url'] === route('schedule') => 'calendar-days',
        $child['url'] === route('departments.index') => 'building-office-2',
        $child['url'] === route('contact') => 'map-pin',
        (bool) preg_match('/ประวัติ|history/iu', $child['label']) => 'clock',
        (bool) preg_match('/วิสัยทัศน์|พันธกิจ|vision|mission/iu', $child['label']) => 'eye',
        (bool) preg_match('/ค่านิยม|values/iu', $child['label']) => 'heart',
        (bool) preg_match('/ยุทธศาสตร์|เข็มมุ่ง|strateg/iu', $child['label']) => 'flag',
        (bool) preg_match('/โครงสร้าง|structure/iu', $child['label']) => 'rectangle-group',
        (bool) preg_match('/ผู้อำนวยการ|กรรมการ|บริหาร|director|board/iu', $child['label']) => 'user-group',
        (bool) preg_match('/แผนผัง|แผนที่|map/iu', $child['label']) => 'map',
        default => 'document-text',
    };
@endphp
<x-layouts.site :title="__('About us')" :description="__('About :name: its history, vision, structure, departments and how to reach it.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap max-w-4xl py-10 sm:py-14">
        <p class="eyebrow">{{ __('About us') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ App\Support\SiteSettings::name() }}</h1>

        <ul class="mt-8 grid gap-3 sm:grid-cols-2">
            @foreach ($about['children'] ?? [] as $child)
                <li>
                    <a href="{{ $child['url'] }}" class="card flex h-full items-center gap-4 px-5 py-4 text-ink no-underline">
                        <span class="door-mark" aria-hidden="true"><x-dynamic-component :component="'heroicon-o-'.$mark($child)" class="size-5" /></span>
                        <span class="min-w-0 flex-1">{{ $child['label'] }}</span>
                        <x-heroicon-m-chevron-right class="size-5 shrink-0 text-brand" aria-hidden="true" />
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</x-layouts.site>
