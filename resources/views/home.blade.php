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
    // What visitors most often phone to ask, beside the name: what is open today.
    $sessions = App\Models\ClinicSession::today();
    $openNow = $sessions->filter->isOpenNow()->count();
    $announcements = $shows('announcements') ? App\Models\Announcement::current()->with('type')->inListOrder()->limit(5)->get() : collect();
    // The slides sit beside the announcements, unless an administrator gave them a section of their own.
    $slides = $shows('announcements') && ! $shows('slides') ? App\Models\Announcement::slides() : collect();
    // The departments that posted most lately, newest first: a way in to their own pages.
    $posting = $shows('announcements')
        ? App\Models\Department::shown()->whereKey($lately = App\Models\Announcement::current()->whereNotNull('department_id')->latest('starts_at')->limit(60)->pluck('department_id')->unique()->take(8)->values())->get()->sortBy(fn ($one) => $lately->search($one->id))->values()
        : collect();
    // Beside the news as the page is read on: what has been given a day that is still to come, and what is read most.
    $coming = $shows('announcements') ? App\Models\Announcement::published()->whereDate('event_on', '>=', now()->toDateString())->orderBy('event_on')->orderBy('id')->limit(3)->get() : collect();
    $mostRead = $shows('announcements') ? App\Models\Announcement::mostRead(4) : collect();
    $types = $shows('announcements') ? App\Models\AnnouncementType::orderBy('sort_order')->orderBy('name')->get() : collect();

    // What people most often come to do, as large tiles under the first screen. A place is offered once it has something in it.
    $actions = array_values(array_filter([
        [route('schedule'), 'Clinic schedule', 'When each clinic is open', 'calendar-days'],
        [route('phones'), 'Phone numbers', 'Every department\'s number', 'phone'],
        [route('announcements.index'), 'Announcements', 'News and notices', 'megaphone'],
        [route('departments.index'), 'Departments', 'Who looks after what', 'building-office-2'],
        App\Models\StaffLink::forThePublic()->exists() ? [route('services'), 'Services online', 'Without coming in', 'computer-desktop'] : null,
        App\Models\Faq::shown()->exists() ? [route('faq'), 'Questions people ask', 'Answers before you call', 'question-mark-circle'] : null,
        App\Models\Announcement::hasEvents() ? [route('calendar'), 'Calendar', 'What happens when', 'calendar'] : null,
        [route('contact'), 'Contact and map', 'How to reach us', 'map-pin'],
    ]));
    // (Not "$address": the list of the other parts of the site uses that name for where each line leads.)
    $where = SiteSettings::get('contact.address');
    $departments = $shows('departments') ? App\Models\Department::shown()->limit(6)->get() : collect();
@endphp
<x-layouts.site :description="__('News, announcements and services of :name.', ['name' => $name])">
    @foreach ($sections as $section)
    @switch($section)
    @case('slides')
    <x-site.banners part="banners" />
    @break

    @case('hero')
    {{--
        The first screen answers what people come for before they scroll: whose site this is, how to call,
        and what is open today (drawn as an editor's window, read as a list).
    --}}
    <section class="home-screen">
        @if ($photo = SiteSettings::heroUrl())
            {{-- The hospital itself, faded behind the words (Site settings > The hospital). Decoration: it says nothing a reader needs. --}}
            <img src="{{ $photo }}" alt="" aria-hidden="true" class="hero-photo" fetchpriority="high">
            <div class="hero-veil" aria-hidden="true"></div>
        @else
            {{-- No photograph yet: a drawing in the site's colour stands in, so the first screen is not bare. --}}
            <div class="hero-art" aria-hidden="true"><span></span><span></span><span></span></div>
        @endif
        <div @class(['wrap grid items-center gap-12 py-14 sm:py-20', 'lg:grid-cols-2' => $sessions->isNotEmpty()])>
            <div @class(['text-center', 'lg:text-left' => $sessions->isNotEmpty()])>
                @if ($logo = SiteSettings::logoUrl())
                    <img src="{{ $logo }}" alt="" @class(['mx-auto mb-6 h-20 w-auto', 'lg:mx-0' => $sessions->isNotEmpty()])>
                @endif
                <p class="eyebrow">{{ __('Welcome to') }}</p>
                <h1 class="hero-name mt-4 text-4xl leading-tight sm:text-5xl md:text-6xl">{{ $name }}</h1>
                <p @class(['mx-auto mt-6 max-w-xl text-lg text-muted sm:text-xl', 'lg:mx-0' => $sessions->isNotEmpty()])>{{ $motto ? '“'.$motto.'”' : __('News, announcements and services of the hospital, in one place.') }}</p>

                @if ($phone || $emergency)
                    <div @class(['mt-8 flex flex-wrap justify-center gap-3', 'lg:justify-start' => $sessions->isNotEmpty()])>
                        @if ($phone)
                            <a href="{{ $dial($phone) }}" class="btn btn-brand"><x-heroicon-s-phone class="size-5" aria-hidden="true" />{{ __('Call :number', ['number' => $phone]) }}</a>
                        @endif
                        @if ($emergency)
                            <a href="{{ $dial($emergency) }}" class="btn btn-line">{{ __('Emergency :number', ['number' => $emergency]) }}</a>
                        @endif
                    </div>
                @endif

                {{-- One line, as a status bar: is anything open this minute. --}}
                @if ($openNow)
                    <p class="mt-6 font-mono text-sm text-muted">
                        <span class="mr-2 inline-block size-2 animate-pulse-dot rounded-full bg-syn-function align-middle" aria-hidden="true"></span>{{ trans_choice('{1} :count clinic or service is open now|[2,*] :count clinics and services are open now', $openNow, ['count' => $openNow]) }}
                    </p>
                @endif
            </div>

            @if ($sessions->isNotEmpty())
                <section class="code-block" aria-labelledby="today-heading">
                    <div class="code-header">
                        <span class="code-dot bg-danger" aria-hidden="true"></span>
                        <span class="code-dot bg-syn-string" aria-hidden="true"></span>
                        <span class="code-dot bg-syn-function" aria-hidden="true"></span>
                        <h2 id="today-heading" class="ml-3 text-sm font-medium text-muted">{{ __('Open today') }} · <span @class(['day-chip' => App\Support\Theme::touchOfTheDay()])>{{ __(App\Models\ClinicSession::DAYS[now()->dayOfWeek]) }}</span></h2>
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
                </section>
            @endif
        </div>
    </section>

    {{-- What people most often come to do: a tile each, in reach before any scrolling on a wide screen. --}}
    <section class="border-y border-line bg-surface" aria-label="{{ __('What would you like to do?') }}">
        <ul @class(['wrap grid grid-cols-2 gap-3 py-6 sm:grid-cols-3', 'lg:grid-cols-5' => count($actions) === 5, 'lg:grid-cols-6' => count($actions) >= 6])>
            @foreach (array_slice($actions, 0, 6) as [$to, $label, $about, $mark])
                {{-- On a phone, two to a row: an odd one out takes the whole of the last row. --}}
                <li @class(['max-sm:col-span-2' => $loop->last && $loop->count % 2 === 1])>
                    <a href="{{ $to }}" class="card group flex h-full flex-col items-start gap-3 bg-page px-4 py-4 text-ink no-underline">
                        <span class="door-mark" aria-hidden="true"><x-dynamic-component :component="'heroicon-o-'.$mark" class="size-5" /></span>
                        <span>
                            <span class="block font-medium leading-snug group-hover:text-brand">{{ __($label) }}</span>
                            <span class="mt-0.5 block text-sm text-muted">{{ __($about) }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
    @break

    @case('announcements')
    @if ($announcements->isNotEmpty() || $slides->isNotEmpty())
        {{--
            What is new, in one place: the pictures staff put up, one at a time in a frame of one size, beside
            the newest few announcements. The whole list, with its kinds and years, is a page of its own.
        --}}
        <section id="news" class="scroll-mt-16">
            <div class="wrap py-16 sm:py-24">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="eyebrow">{{ __('Announcements') }}</p>
                        <h2 class="mt-2 text-3xl sm:text-4xl">{{ __('News') }}</h2>
                    </div>
                    <a href="{{ route('announcements.index') }}" class="btn btn-line">{{ __('All announcements') }} &rarr;</a>
                </div>

                <div @class(['mt-10 grid items-start gap-8', 'lg:grid-cols-5' => $slides->isNotEmpty() && $announcements->isNotEmpty()])>
                    @if ($slides->isNotEmpty())
                        <div class="lg:col-span-3"><x-site.banners part="stage" :banners="$slides" frame="aspect-[4/3]" /></div>
                    @endif
                    @if ($announcements->isNotEmpty())
                        <ul @class(['card p-0', 'lg:col-span-2' => $slides->isNotEmpty(), 'mx-auto w-full max-w-4xl' => $slides->isEmpty()])>
                            @foreach ($announcements as $announcement)
                                <x-site.announcement-item :announcement="$announcement" />
                            @endforeach
                        </ul>
                    @endif
                </div>

                {{-- Each kind of announcement, one press away. --}}
                @if ($types->isNotEmpty())
                    <nav class="mt-8 flex flex-wrap gap-2" aria-label="{{ __('Types') }}">
                        @foreach ($types as $one)
                            <a href="{{ route('announcements.index', ['type' => $one->slug]) }}" class="chip gap-2"><x-site.type-mark :type="$one" bare />{{ $one->localName() }}</a>
                        @endforeach
                    </nav>
                @endif

                {{-- What is coming up, and what others have read most: each only when there is something to say. --}}
                @if ($coming->isNotEmpty() || $mostRead->isNotEmpty())
                    <div @class(['mt-10 grid gap-8', 'lg:grid-cols-2' => $coming->isNotEmpty() && $mostRead->isNotEmpty()])>
                        @if ($coming->isNotEmpty())
                            <div>
                                <h3 class="flex items-center gap-2 text-xl"><x-heroicon-o-calendar-days class="size-5 text-brand" aria-hidden="true" />{{ __('Coming up') }}</h3>
                                <ul class="card mt-3 p-0">
                                    @foreach ($coming as $one)
                                        <li class="border-b border-line last:border-b-0">
                                            <a href="{{ route('announcements.show', $one) }}" class="group flex items-center gap-3 px-4 py-3 no-underline">
                                                <span class="flex w-12 shrink-0 flex-col items-center rounded-lg border border-line bg-page py-1 text-center" aria-hidden="true">
                                                    <span class="font-mono text-xl font-semibold leading-none text-brand">{{ $one->event_on->day }}</span>
                                                    <span class="mt-0.5 font-mono text-xs text-muted">{{ $one->event_on->locale(app()->getLocale())->translatedFormat('M') }}</span>
                                                </span>
                                                <span class="line-clamp-2 min-w-0 flex-1 text-ink group-hover:text-brand">{{ $one->localTitle() }}<span class="sr-only"> ({{ $one->event_on->locale(app()->getLocale())->translatedFormat('j F') }})</span></span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="mt-3 text-sm"><a href="{{ route('calendar') }}" class="font-semibold text-brand underline">{{ __('Calendar') }} &rarr;</a></p>
                            </div>
                        @endif
                        @if ($mostRead->isNotEmpty())
                            <div>
                                <h3 class="flex items-center gap-2 text-xl"><x-heroicon-o-fire class="size-5 text-brand" aria-hidden="true" />{{ __('Most read') }}</h3>
                                <ol class="card mt-3 p-0">
                                    @foreach ($mostRead as $one)
                                        <li class="border-b border-line last:border-b-0">
                                            <a href="{{ route('announcements.show', $one) }}" class="group flex items-start gap-3 px-4 py-3 no-underline">
                                                <span class="w-6 shrink-0 text-center font-mono text-xl font-semibold text-brand" aria-hidden="true">{{ $loop->iteration }}</span>
                                                <span class="line-clamp-2 min-w-0 flex-1 text-ink group-hover:text-brand">{{ $one->localTitle() }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($posting->isNotEmpty())
                    <div class="mt-10">
                        <h3 class="eyebrow">{{ __('From the departments') }}</h3>
                        <ul class="mt-4 flex flex-wrap gap-3">
                            @foreach ($posting as $one)
                                <li>
                                    <a href="{{ route('departments.show', $one) }}" class="chip min-h-12 gap-2 py-1 pl-1 pr-4 text-ink">
                                        <x-site.department-avatar :department="$one" class="size-9 text-sm" />{{ $one->localName() }}
                                    </a>
                                </li>
                            @endforeach
                            <li><a href="{{ route('posts') }}" class="chip min-h-12 gap-2 border-brand text-brand"><x-heroicon-m-newspaper class="size-4" aria-hidden="true" />{{ __('Read the feed') }} &rarr;</a></li>
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    @endif
    @break

    @case('shortcuts')
    {{--
        The rest of the site as one list, drawn as a window of the editor: each line a part of the site,
        what is in it, and the way there. A list, not a wall of cards: read top to bottom in a few seconds.
    --}}
    <section class="bg-surface" aria-label="{{ __('Shortcuts') }}">
        <div class="wrap py-16 sm:py-24">
            <div class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">{{ __('Shortcuts') }}</p>
                <h2 class="mt-2 text-3xl sm:text-4xl">{{ __('The rest of the site') }}</h2>
                <p class="mt-4 text-lg text-muted">{{ __('Each has a page of its own. Choose one from the list.') }}</p>
            </div>
            <ul class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    [route('announcements.index', ['type' => 'procurement']), 'Procurement', 'Procurement plans, invitations to bid and results, by fiscal year.', 'clipboard-document-list'],
                    [route('announcements.index', ['type' => 'activities']), 'Activities', 'Photographs and news of what the hospital has been doing.', 'camera'],
                    [route('announcements.index', ['type' => 'knowledge']), 'Health knowledge', 'Health knowledge written by the hospital\'s own staff.', 'heart'],
                    [route('schedule'), 'Clinic schedule', 'Which clinic is open on which day, and at what time.', 'calendar-days'],
                    [route('about'), 'About us', 'History, vision, structure and the people who run the hospital.', 'building-office-2'],
                    [route('contact'), 'Contact and map', 'Address, phone numbers, opening hours and a map.', 'map-pin'],
                ] as $index => [$address, $label, $about, $mark])
                    <li>
                        <a href="{{ $address }}" id="door-{{ $index }}" class="card group flex h-full flex-col bg-page text-ink no-underline">
                            <span class="flex items-center justify-between gap-3">
                                <span class="door-mark" aria-hidden="true"><x-dynamic-component :component="'heroicon-o-'.$mark" class="size-5" /></span>
                                <span class="font-mono text-sm text-syn-comment" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            </span>
                            <span class="mt-4 block text-lg font-medium group-hover:text-brand">{{ __($label) }}</span>
                            <span class="mt-1 block flex-1 text-sm text-muted">{{ __($about) }}</span>
                            <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand">{{ __('Open') }}<x-heroicon-m-arrow-right class="size-4 transition group-hover:translate-x-1" aria-hidden="true" /></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
    @break

    @case('identity')
    @if ($identity)
        {{--
            Vision, mission and core values, one at a time. The tabs are radio buttons and the stylesheet shows the
            panel of the one that is ticked (.tabs in app.css): no script, and the arrow keys move between them.
        --}}
        <section id="identity" class="scroll-mt-16">
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
                <p class="mt-10 text-center"><a href="{{ route('about') }}" class="btn btn-line">{{ __('Get to know the hospital') }} &rarr;</a></p>
            </div>
        </section>
    @endif
    @break

    @case('departments')
    @if ($departments->isNotEmpty())
        <section>
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

    @endswitch
    @endforeach

    {{-- The last thing on the page, whatever the sections above: the numbers to call, large. Left out when there are none. --}}
    @if ($phone || $emergency)
        <section class="home-screen !min-h-0 border-t border-line" aria-labelledby="reach-heading">
            <div class="wrap max-w-4xl py-16 text-center sm:py-20">
                <p class="eyebrow">{{ __('Contact us') }}</p>
                <h2 id="reach-heading" class="mt-2 text-3xl sm:text-4xl">{{ __('We are here when you need us') }}</h2>
                <div @class(['mx-auto mt-8 grid max-w-3xl gap-4', 'sm:grid-cols-2' => $emergency && $phone])>
                    @if ($emergency)
                        <a href="{{ $dial($emergency) }}" class="card border-danger text-ink no-underline">
                            <span class="block text-sm text-danger">{{ __('Emergency, 24 hours a day') }}</span>
                            <span class="block font-mono text-4xl font-semibold text-danger">{{ $emergency }}</span>
                        </a>
                    @endif
                    @if ($phone)
                        <a href="{{ $dial($phone) }}" class="card text-ink no-underline">
                            <span class="block text-sm text-muted">{{ __('The hospital') }}</span>
                            <span class="block font-mono text-4xl font-semibold text-brand">{{ $phone }}</span>
                        </a>
                    @endif
                </div>
                @if ($where)
                    <p class="mx-auto mt-6 inline-flex max-w-2xl items-start gap-2 text-muted"><x-heroicon-m-map-pin class="mt-1 size-5 shrink-0 text-brand" aria-hidden="true" /><span>{{ $where }}</span></p>
                @endif
                <p class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('contact') }}" class="btn btn-brand"><x-heroicon-m-map-pin class="size-5" aria-hidden="true" />{{ __('Map and directions') }}</a>
                    <a href="{{ route('phones') }}" class="btn btn-line">{{ __('Phone numbers of every department') }}</a>
                </p>
            </div>
        </section>
    @endif

    <x-site.banners part="popup" />
</x-layouts.site>
