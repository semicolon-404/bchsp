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

    // What is open today, shown straight under the slides: what visitors most often come to find out.
    $sessions = App\Models\ClinicSession::today();

    // What the site holds, as the buttons of the first slide. One whose page is not there is left out.
    $doors = array_filter([
        ['schedule', [], 'calendar-days', 'Clinic schedule'],
        ['announcements.index', [], 'megaphone', 'News'],
        App\Models\AnnouncementType::where('slug', App\Support\Menus::PROCUREMENT)->exists() ? ['announcements.index', ['type' => App\Support\Menus::PROCUREMENT], 'clipboard-document-list', 'Procurement'] : null,
        ['ita', [], 'shield-check', 'ITA'],
        ['blog.index', [], 'newspaper', 'Health articles'],
        ['contact', [], 'map-pin', 'Contact and map'],
    ]);

    $announcements = App\Models\Announcement::current()->with('type')->inListOrder()->limit(6)->get();
    $departments = App\Models\Department::shown()->limit(6)->get();
    $articles = App\Models\BlogPost::published()->with('category')->latest('published_at')->limit(3)->get();
@endphp
<x-layouts.site :description="__('News, announcements and services of :name.', ['name' => $name])">
    @foreach (App\Support\HomeLayout::shown() as $section)
    @switch($section)
    @case('slides')
    {{--
        The stage at the top of the page: a large carousel. Its first slide is the site itself: the hospital's
        name, the numbers to call, and a button for each thing the site holds. The slides after it are the
        pictures staff put up (App\Models\Banner). The first slide is words, not a picture, so it reads on any
        screen and in both languages.
    --}}
    <x-site.banners part="banners">
        <div class="mx-auto flex h-full max-w-4xl flex-col items-center justify-center px-2 py-8 text-center sm:py-12">
            <p class="inline-flex items-center gap-2 rounded-full border border-line bg-page px-3 py-1 text-sm text-muted">
                <span class="size-2 animate-pulse-dot rounded-full bg-brand" aria-hidden="true"></span>
                {{ __('Caring for the community') }}
            </p>
            <h1 class="mt-5 text-3xl leading-tight sm:text-4xl md:text-5xl">{{ $name }}</h1>
            <p class="mt-4 max-w-xl text-lg text-muted">{{ __('News, announcements and services of the hospital, in one place.') }}</p>

            @if ($phone || $emergency)
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    @if ($phone)
                        <a href="{{ $dial($phone) }}" class="btn btn-brand">
                            <x-heroicon-s-phone class="size-5" aria-hidden="true" />
                            {{ __('Call :number', ['number' => $phone]) }}
                        </a>
                    @endif
                    @if ($emergency)
                        <a href="{{ $dial($emergency) }}" class="btn btn-line">{{ __('Emergency :number', ['number' => $emergency]) }}</a>
                    @endif
                </div>
            @endif

            <nav class="mt-8 grid w-full grid-cols-2 gap-2 sm:grid-cols-3" aria-label="{{ __('Shortcuts') }}">
                @foreach ($doors as [$route, $parameters, $icon, $label])
                    <a href="{{ route($route, $parameters) }}" class="card flex items-center gap-2 bg-page px-3 py-3 text-left text-sm font-medium text-ink no-underline sm:text-base">
                        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5 shrink-0 text-brand" aria-hidden="true" />
                        <span class="min-w-0">{{ __($label) }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
    </x-site.banners>
    @break

    @case('today')
    @if ($sessions->isNotEmpty())
        <section class="border-b border-line" aria-labelledby="today-heading">
            <div class="wrap py-8">
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <h2 id="today-heading" class="text-xl">{{ __('Open today') }} <span class="text-muted">· {{ __(App\Models\ClinicSession::DAYS[now()->dayOfWeek]) }}</span></h2>
                    <a href="{{ route('schedule') }}" class="text-sm font-semibold text-brand underline">{{ __('The whole week') }} &rarr;</a>
                </div>
                <ul class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($sessions->take(9) as $session)
                        <li class="card flex items-baseline gap-3 px-4 py-3">
                            <span class="shrink-0 whitespace-nowrap font-mono text-sm text-syn-string">{{ $session->hours() }}</span>
                            <span class="min-w-0 flex-1">{{ $session->localName() }}</span>
                            @if ($session->isOpenNow())
                                <span class="shrink-0 font-mono text-xs font-semibold text-syn-function">{{ __('Open now') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif
    @break

    @case('announcements')
    @if ($announcements->isNotEmpty())
        <section class="border-b border-line bg-surface">
            <div class="wrap py-12 sm:py-16">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="eyebrow">{{ __('Announcements') }}</p>
                        <h2 class="mt-2 text-2xl sm:text-3xl">{{ __('Announcements from the hospital') }}</h2>
                    </div>
                    <a href="{{ route('announcements.index') }}" class="btn btn-line">{{ __('All announcements') }} &rarr;</a>
                </div>
                <ul class="card mt-8 bg-page p-0">
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
