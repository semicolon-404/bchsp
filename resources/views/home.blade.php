{{--
    The home page: its sections in the order an administrator gave them, without the ones switched off
    (App\Support\HomeLayout; System > Site settings > "Home page layout"). A section with nothing in it takes no room.
--}}
@php
    use App\Support\SiteSettings;

    $name = SiteSettings::name();
    $phone = SiteSettings::get('contact.phone');
    $emergency = SiteSettings::get('contact.emergency_phone');
    $email = SiteSettings::get('contact.email');
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);

    // What the "editor window" lists: only what has been filled in.
    $facts = array_filter(['phone' => $phone, 'emergency' => $emergency, 'email' => $email]);

    // Today's clinics take the place beside the heading; the contact details drawn as code are what shows until a schedule exists.
    $sessions = App\Models\ClinicSession::today();

    // Only what the sections that are switched on will draw is read.
    $sections = App\Support\HomeLayout::shown();
    $announcements = in_array('announcements', $sections, true) ? App\Models\Announcement::current()->with('type')->inListOrder()->limit(4)->get() : collect();
    $departments = in_array('departments', $sections, true) ? App\Models\Department::shown()->limit(6)->get() : collect();
    $articles = in_array('articles', $sections, true) ? App\Models\BlogPost::published()->with('category')->latest('published_at')->limit(3)->get() : collect();
@endphp
<x-layouts.site :description="__('News, announcements and services of :name.', ['name' => $name])">
    @foreach ($sections as $section)
    @switch($section)
    @case('slides')
    <x-site.banners part="banners" />
    @break

    @case('hero')
    {{-- Like every section of this page it fills the window, less the header: one thing to look at at a time. --}}
    <section class="home-screen border-b border-line">
        <div class="wrap grid items-center gap-10 py-14 sm:py-20 lg:grid-cols-2">
            <div class="text-center lg:text-left">
                <p class="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-3 py-1 text-sm text-muted">
                    <span class="size-2 animate-pulse-dot rounded-full bg-brand" aria-hidden="true"></span>
                    {{ __('Caring for the community') }}
                </p>
                <h1 class="mt-6 text-3xl leading-tight sm:text-4xl md:text-5xl">{{ $name }}</h1>
                <p class="mx-auto mt-5 max-w-xl text-lg text-muted lg:mx-0">{{ __('News, announcements and services of the hospital, in one place.') }}</p>

                @if ($phone || $emergency)
                    <div class="mt-8 flex flex-wrap justify-center gap-3 lg:justify-start">
                        @if ($phone)
                            <a href="{{ $dial($phone) }}" class="btn btn-brand">
                                <x-heroicon-s-phone class="size-5" aria-hidden="true" />
                                {{ __('Call :number', ['number' => $phone]) }}
                            </a>
                        @endif
                        @if ($emergency)
                            <a href="{{ $dial($emergency) }}" class="btn btn-line">
                                {{ __('Emergency :number', ['number' => $emergency]) }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            @if ($sessions->isNotEmpty())
                {{-- What visitors most often phone to ask: what is open today. Drawn as an editor's window, read as a list. --}}
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
            {{-- The same contact details again, drawn as a file open in an editor. Decoration: screen readers skip it. --}}
            @elseif ($facts)
                <div class="code-block" aria-hidden="true">
                    <div class="code-header">
                        <span class="code-dot bg-danger"></span>
                        <span class="code-dot bg-syn-string"></span>
                        <span class="code-dot bg-syn-function"></span>
                        <span class="ml-3 font-mono text-sm text-muted">contact.ts</span>
                    </div>
                    <pre class="code-body"><code><span class="text-syn-comment">// {{ $name }}</span>
<span class="text-syn-keyword">const</span> <span class="text-syn-variable">hospital</span> = {
@foreach ($facts as $key => $value)
  <span class="text-syn-function">{{ $key }}</span>: <span class="text-syn-string">"{{ $value }}"</span>,
@endforeach
};</code></pre>
                </div>
            @endif
        </div>
    </section>
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

    @case('announcements')
    @if ($announcements->isNotEmpty())
        {{-- The one part that shows some of what is inside: the newest few. The whole list, with its kinds and years, is a page of its own. --}}
        <section class="home-screen border-b border-line">
            <div class="wrap grid items-center gap-8 py-16 sm:py-24 md:grid-cols-2">
                <div class="text-center md:text-left">
                    <p class="eyebrow">{{ __('Announcements') }}</p>
                    <h2 class="mt-3 text-3xl sm:text-4xl md:text-5xl">{{ __('News') }}</h2>
                    <p class="mx-auto mt-4 max-w-md text-lg text-muted md:mx-0">{{ __('News and announcements from the hospital, the newest first.') }}</p>
                    <a href="{{ route('announcements.index') }}" class="btn btn-brand mt-8">{{ __('All announcements') }} &rarr;</a>
                </div>
                <ul class="card p-0">
                    @foreach ($announcements as $announcement)
                        <x-site.announcement-row :announcement="$announcement" />
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
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
