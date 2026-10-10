{{--
    The home page: its sections in the order an administrator gave them, without the ones switched off
    (App\Support\HomeLayout; System > Site settings > "Home page layout"). A section with nothing in it takes no room.
--}}
@php
    use App\Support\SiteSettings;

    $name = SiteSettings::name();
    $phone = SiteSettings::get('contact.phone');
    $emergency = SiteSettings::get('contact.emergency_phone');
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
    $motto = SiteSettings::get('site.motto');

    // What the hospital says of itself: each a few lines an administrator typed, one item to a line. Blank ones are left out.
    $lines = fn (string $key) => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) SiteSettings::get($key)))));
    $identity = array_filter(['Vision' => $lines('identity.vision'), 'Mission' => $lines('identity.mission'), 'Core values' => $lines('identity.values')]);

    // Only what the sections that are switched on will draw is read.
    $sections = App\Support\HomeLayout::shown();
    $shows = fn (string $section) => in_array($section, $sections, true);
    $slides = App\Models\Banner::slides();
    $sessions = $shows('today') ? App\Models\ClinicSession::today() : collect();
    $departments = $shows('departments') ? App\Models\Department::shown()->limit(6)->get() : collect();
    $articles = $shows('articles') ? App\Models\BlogPost::published()->with('category')->latest('published_at')->limit(3)->get() : collect();
@endphp
<x-layouts.site :description="__('News, announcements and services of :name.', ['name' => $name])">
    @foreach ($sections as $section)
    @switch($section)
    @case('slides')
    <x-site.banners part="banners" />
    @break

    @case('hero')
    {{--
        The first thing seen, as tall as the window: the welcome, then the slides staff put up, one at a time.
        They can be swiped; resources/js/slider.js adds the arrows, the dots and moving on by itself.
    --}}
    <section class="relative border-b border-line bg-surface" aria-label="{{ __('Welcome') }}" @if ($slides->isNotEmpty()) aria-roledescription="carousel" data-slider @endif>
        <ul class="slider-track" data-slider-track>
            <li class="home-screen w-full shrink-0 snap-center" @if ($slides->isNotEmpty()) aria-roledescription="slide" aria-label="1 / {{ $slides->count() + 1 }}" @endif>
                <div class="wrap py-16 text-center">
                    @if ($logo = SiteSettings::logoUrl())
                        <img src="{{ $logo }}" alt="" class="mx-auto mb-6 h-24 w-auto sm:h-28">
                    @endif
                    <p class="eyebrow">{{ __('Welcome to') }}</p>
                    <h1 class="mt-4 text-4xl leading-tight sm:text-5xl md:text-6xl">{{ $name }}</h1>
                    <p class="mx-auto mt-6 max-w-2xl text-lg text-muted sm:text-xl">{{ $motto ? '“'.$motto.'”' : __('News, announcements and services of the hospital, in one place.') }}</p>

                    <div class="mt-10 flex flex-wrap justify-center gap-3">
                        @if ($shows('announcements'))
                            <a href="#news" class="btn btn-brand"><x-heroicon-o-megaphone class="size-5" aria-hidden="true" />{{ __('News') }}</a>
                        @endif
                        @if ($shows('identity') && $identity)
                            <a href="#identity" class="btn btn-line"><x-heroicon-o-information-circle class="size-5" aria-hidden="true" />{{ __('About the organisation') }}</a>
                        @endif
                        @if ($phone)
                            <a href="{{ $dial($phone) }}" class="btn btn-line"><x-heroicon-s-phone class="size-5" aria-hidden="true" />{{ __('Call :number', ['number' => $phone]) }}</a>
                        @endif
                        @if ($emergency)
                            <a href="{{ $dial($emergency) }}" class="btn btn-line">{{ __('Emergency :number', ['number' => $emergency]) }}</a>
                        @endif
                    </div>
                </div>
            </li>
            <x-site.banners part="screens" :banners="$slides" />
        </ul>

        @if ($slides->isNotEmpty())
            <button type="button" class="slider-arrow left-2 sm:left-6" data-slider-prev aria-label="{{ __('Previous slide') }}"><x-heroicon-m-chevron-left class="size-6" aria-hidden="true" /></button>
            <button type="button" class="slider-arrow right-2 sm:right-6" data-slider-next aria-label="{{ __('Next slide') }}"><x-heroicon-m-chevron-right class="size-6" aria-hidden="true" /></button>
            <div class="absolute inset-x-0 bottom-4 flex justify-center gap-1">
                @foreach (range(1, $slides->count() + 1) as $number)
                    <button type="button" class="slider-dot" data-slider-dot aria-current="{{ $number === 1 ? 'true' : 'false' }}" aria-label="{{ __('Slide :number', ['number' => $number]) }}"></button>
                @endforeach
            </div>
        @endif
    </section>
    @break

    @case('announcements')
    {{-- Nothing is listed here: what the news is, in a sentence, and the way to all of it. --}}
    <section id="news" class="home-screen scroll-mt-16 border-b border-line">
        <div class="wrap py-16 text-center sm:py-24">
            <h2 class="text-3xl sm:text-4xl">{{ __('News') }}</h2>
            <span class="mx-auto mt-4 block h-1 w-16 rounded-full bg-brand" aria-hidden="true"></span>
            <div class="card mx-auto mt-10 max-w-3xl px-6 py-12 sm:px-12 sm:py-16">
                <x-heroicon-o-newspaper class="mx-auto size-16 text-brand" aria-hidden="true" />
                <h3 class="mt-6 text-2xl">{{ __('Follow the latest news and announcements') }}</h3>
                <p class="mx-auto mt-4 max-w-xl text-lg text-muted">{{ __('Public relations news, procurement notices and the activities of :name, all in one place.', ['name' => $name]) }}</p>
                <a href="{{ route('announcements.index') }}" class="btn btn-brand mt-8"><x-heroicon-m-arrow-right-circle class="size-5" aria-hidden="true" />{{ __('Go to the news page') }}</a>
            </div>
        </div>
    </section>
    @break

    @case('identity')
    @if ($identity)
        {{--
            Vision, mission and core values, one at a time. The tabs are radio buttons and the stylesheet shows the
            panel of the one that is ticked (.tabs in app.css): no script, and the arrow keys move between them.
        --}}
        <section id="identity" class="home-screen scroll-mt-16 border-b border-line bg-surface">
            <div class="wrap py-16 sm:py-24">
                <h2 class="text-center text-3xl sm:text-4xl">{{ __('Identity of the organisation') }}</h2>
                <span class="mx-auto mt-4 block h-1 w-16 rounded-full bg-brand" aria-hidden="true"></span>

                <div class="tabs mx-auto mt-10 max-w-4xl">
                    @foreach (array_keys($identity) as $index => $heading)
                        <input type="radio" name="identity" id="identity-{{ $index }}" class="sr-only" @checked($index === 0)>
                    @endforeach
                    <div class="flex flex-wrap justify-center gap-2">
                        @foreach (array_keys($identity) as $index => $heading)
                            <label for="identity-{{ $index }}" class="chip cursor-pointer">{{ __($heading) }}</label>
                        @endforeach
                    </div>
                    <div class="mt-8">
                        @foreach ($identity as $heading => $items)
                            <div class="tab-panel card bg-page px-6 py-10 sm:px-10">
                                <p class="eyebrow">{{ __($heading) }}</p>
                                @if ($heading === 'Vision')
                                    <p class="mt-4 text-2xl leading-relaxed sm:text-3xl">“{{ $items[0] }}”</p>
                                    @foreach (array_slice($items, 1) as $item)
                                        <p class="mt-4 text-muted">{{ $item }}</p>
                                    @endforeach
                                @elseif ($heading === 'Mission')
                                    <ol class="mt-6 grid gap-3 sm:grid-cols-2">
                                        @foreach ($items as $number => $item)
                                            <li class="flex gap-3 rounded-lg border border-line border-l-4 border-l-brand bg-surface px-4 py-3"><span class="font-mono text-brand">{{ $number + 1 }}</span><span>{{ $item }}</span></li>
                                        @endforeach
                                    </ol>
                                @else
                                    <ul class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                        @foreach ($items as $item)
                                            <li class="rounded-lg border border-line bg-surface px-3 py-4 text-center font-medium">{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
    @break

    @case('today')
    @if ($sessions->isNotEmpty())
        {{-- What visitors most often phone to ask: what is open today. Drawn as an editor's window, read as a list. --}}
        <section class="border-b border-line" aria-labelledby="today-heading">
            <div class="wrap max-w-3xl py-16 sm:py-24">
                <div class="code-block">
                    <div class="code-header">
                        <span class="code-dot bg-danger" aria-hidden="true"></span>
                        <span class="code-dot bg-syn-string" aria-hidden="true"></span>
                        <span class="code-dot bg-syn-function" aria-hidden="true"></span>
                        <h2 id="today-heading" class="ml-3 text-sm font-medium text-muted">{{ __('Open today') }} · {{ __(App\Models\ClinicSession::DAYS[now()->dayOfWeek]) }}</h2>
                    </div>
                    <ul class="px-4 py-2">
                        @foreach ($sessions->take(7) as $session)
                            <li class="flex items-baseline gap-3 border-b border-line py-2 last:border-b-0">
                                <span class="w-28 shrink-0 font-mono text-sm text-syn-string">{{ $session->hours() }}</span>
                                <span class="min-w-0 flex-1">{{ $session->localName() }}</span>
                                @if ($session->isOpenNow())
                                    <span class="shrink-0 font-mono text-xs font-semibold text-syn-function">{{ __('Open now') }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <p class="border-t border-line px-4 py-3 text-sm"><a href="{{ route('schedule') }}" class="font-semibold text-brand underline">{{ __('The whole week') }} &rarr;</a></p>
                </div>
            </div>
        </section>
    @endif
    @break

    @case('shortcuts')
    {{--
        A section for each part of the site, one under another: what it is, in a sentence, and the way in.
        Nothing is listed here: each thing is read on its own page. The sides and the backgrounds alternate.
    --}}
    <div role="group" aria-label="{{ __('Shortcuts') }}">
        @foreach ([
            [route('announcements.index', ['type' => 'procurement']), 'clipboard-document-list', 'Procurement', 'Procurement plans, invitations to bid and results, by fiscal year.'],
            [route('posters'), 'photo', 'Campaigns and notices', 'Campaign posters and one-page notices from the hospital.'],
            [route('blog.index'), 'book-open', 'Health knowledge and news', 'Health knowledge, and photographs of what the hospital has been doing.'],
            [route('schedule'), 'calendar-days', 'Clinic schedule', 'Which clinic is open on which day, and at what time.'],
            [route('contact'), 'map-pin', 'Contact and map', 'Address, phone numbers, opening hours and a map.'],
        ] as $index => [$address, $icon, $label, $about])
            <section @class(['home-screen border-b border-line', 'bg-surface' => $index % 2 === 0]) aria-labelledby="door-{{ $index }}">
                <div class="wrap grid items-center gap-8 py-16 sm:py-24 md:grid-cols-2">
                    <div @class(['text-center md:text-left', 'md:order-2' => $index % 2 === 1])>
                        <p class="eyebrow">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</p>
                        <h2 id="door-{{ $index }}" class="mt-3 text-3xl sm:text-4xl md:text-5xl">{{ __($label) }}</h2>
                        <p class="mx-auto mt-4 max-w-md text-lg text-muted md:mx-0">{{ __($about) }}</p>
                        <a href="{{ $address }}" class="btn btn-brand mt-8">{{ __('Go to :name', ['name' => __($label)]) }} &rarr;</a>
                    </div>
                    <div class="hidden justify-center md:flex" aria-hidden="true">
                        <span class="flex size-56 items-center justify-center rounded-3xl border border-line bg-raised text-brand lg:size-72">
                            <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-24 lg:size-28" />
                        </span>
                    </div>
                </div>
            </section>
        @endforeach
    </div>
    @break

    @case('posters')
    <x-site.banners part="posters" />
    @break

    @case('departments')
    @if ($departments->isNotEmpty())
        <section class="border-b border-line">
            <div class="wrap py-12 sm:py-16">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="eyebrow">{{ __('Departments') }}</p>
                        <h2 class="mt-2 text-2xl sm:text-3xl">{{ __('Departments and services') }}</h2>
                    </div>
                    <a href="{{ route('departments.index') }}" class="btn btn-line">{{ __('All departments') }} &rarr;</a>
                </div>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($departments as $department)
                        <x-site.department-card :department="$department" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    @break

    @case('articles')
    @if ($articles->isNotEmpty())
        <section class="border-b border-line">
            <div class="wrap py-12 sm:py-16">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="eyebrow">{{ __('Blog') }}</p>
                        <h2 class="mt-2 text-2xl sm:text-3xl">{{ __('Health knowledge and news') }}</h2>
                    </div>
                    <a href="{{ route('blog.index') }}" class="btn btn-line">{{ __('All articles') }} &rarr;</a>
                </div>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($articles as $post)
                        <x-site.article-card :post="$post" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    @break
    @endswitch
    @endforeach

    <x-site.banners part="popup" />
</x-layouts.site>
