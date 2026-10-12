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
        <div @class(['wrap grid items-center gap-12 py-14 sm:py-20', 'lg:grid-cols-2' => $sessions->isNotEmpty()])>
            <div @class(['text-center', 'lg:text-left' => $sessions->isNotEmpty()])>
                @if ($logo = SiteSettings::logoUrl())
                    <img src="{{ $logo }}" alt="" @class(['mx-auto mb-6 h-20 w-auto', 'lg:mx-0' => $sessions->isNotEmpty()])>
                @endif
                <p class="eyebrow">{{ __('Welcome to') }}</p>
                <h1 class="mt-4 text-4xl leading-tight sm:text-5xl md:text-6xl">{{ $name }}</h1>
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
                </section>
            @endif
        </div>
    </section>
    @break

    @case('announcements')
    @if ($announcements->isNotEmpty() || $slides->isNotEmpty())
        {{--
            What is new, in one place: the pictures staff put up, one at a time in a frame of one size, beside
            the newest few announcements. The whole list, with its kinds and years, is a page of its own.
        --}}
        <section id="news" class="scroll-mt-16 bg-surface">
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
                        <ul @class(['card bg-page p-0', 'lg:col-span-2' => $slides->isNotEmpty(), 'mx-auto w-full max-w-4xl' => $slides->isEmpty()])>
                            @foreach ($announcements as $announcement)
                                <x-site.announcement-row :announcement="$announcement" />
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </section>
    @endif
    @break

    @case('shortcuts')
    {{--
        The rest of the site as one list, drawn as a window of the editor: each line a part of the site,
        what is in it, and the way there. A list, not a wall of cards: read top to bottom in a few seconds.
    --}}
    <section aria-label="{{ __('Shortcuts') }}">
        <div class="wrap grid items-center gap-10 py-16 sm:py-24 lg:grid-cols-5">
            <div class="text-center lg:col-span-2 lg:text-left">
                <p class="eyebrow">{{ __('Shortcuts') }}</p>
                <h2 class="mt-2 text-3xl sm:text-4xl">{{ __('The rest of the site') }}</h2>
                <p class="mx-auto mt-4 max-w-md text-lg text-muted lg:mx-0">{{ __('Each has a page of its own. Choose one from the list.') }}</p>
            </div>
            <div class="code-block lg:col-span-3">
                <div class="code-header" aria-hidden="true">
                    <span class="code-dot bg-danger"></span>
                    <span class="code-dot bg-syn-string"></span>
                    <span class="code-dot bg-syn-function"></span>
                    <span class="ml-3 font-mono text-sm text-muted">sitemap</span>
                </div>
                <ul>
                    @foreach ([
                        [route('announcements.index', ['type' => 'procurement']), 'Procurement', 'Procurement plans, invitations to bid and results, by fiscal year.'],
                        [route('announcements.index', ['type' => 'activities']), 'Activities', 'Photographs and news of what the hospital has been doing.'],
                        [route('announcements.index', ['type' => 'knowledge']), 'Health knowledge', 'Health knowledge written by the hospital\'s own staff.'],
                        [route('schedule'), 'Clinic schedule', 'Which clinic is open on which day, and at what time.'],
                        [route('about'), 'About us', 'History, vision, structure and the people who run the hospital.'],
                        [route('contact'), 'Contact and map', 'Address, phone numbers, opening hours and a map.'],
                    ] as $index => [$address, $label, $about])
                        <li class="border-b border-line last:border-b-0">
                            <a href="{{ $address }}" id="door-{{ $index }}" class="group flex items-center gap-4 px-4 py-4 text-ink no-underline hover:bg-raised">
                                <span class="font-mono text-sm text-syn-comment" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium group-hover:text-brand">{{ __($label) }}</span>
                                    <span class="mt-0.5 block text-sm text-muted">{{ __($about) }}</span>
                                </span>
                                <x-heroicon-m-arrow-right class="size-5 shrink-0 text-muted transition group-hover:translate-x-1 group-hover:text-brand" aria-hidden="true" />
                            </a>
                        </li>
                    @endforeach
                </ul>
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
        <section id="identity" class="scroll-mt-16 bg-surface">
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

    <x-site.banners part="popup" />
</x-layouts.site>
