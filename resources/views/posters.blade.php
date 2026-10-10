{{-- /posters: every slide and poster that is showing now, in one grid. The home page has a door to it. --}}
<x-layouts.site :title="__('Campaigns and notices')" :description="__('Posters and campaign pictures from :name.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap py-10 sm:py-14">
        <p class="eyebrow">{{ __('Posters') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('Campaigns and notices') }}</h1>
        <x-site.banners part="all" />
    </div>
</x-layouts.site>
