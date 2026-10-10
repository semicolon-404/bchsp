{{--
    /about: everything about the hospital, as one page of links: the pages an administrator filed under
    "About us" (System > Pages), then the departments and the contact page. The top bar's "About us" leads here.
--}}
@php($about = collect(App\Support\Menus::items('all'))->firstWhere('key', 'about'))
<x-layouts.site :title="__('About us')" :description="__('About :name: its history, vision, structure, departments and how to reach it.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap max-w-4xl py-10 sm:py-14">
        <p class="eyebrow">{{ __('About us') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ App\Support\SiteSettings::name() }}</h1>

        <ul class="mt-8 grid gap-3 sm:grid-cols-2">
            @foreach ($about['children'] ?? [] as $child)
                <li>
                    <a href="{{ $child['url'] }}" class="card flex h-full items-center justify-between gap-3 px-5 py-4 text-ink no-underline">
                        <span>{{ $child['label'] }}</span>
                        <x-heroicon-m-chevron-right class="size-5 shrink-0 text-brand" aria-hidden="true" />
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</x-layouts.site>
