{{--
    /about: the hospital's profile, the page that presents it. From the top: who it is (its photograph, name, motto,
    how to reach it, and a few figures counted from the site itself), then what it stands for (vision, mission,
    core values), how it began, when it is open, its departments, every page filed under "About us", and how to
    get in touch. A row of links under the top keeps every part one press away.

    Everything is read from what staff keep in the panel (Site settings, Pages, Departments, the clinic schedule):
    a part with nothing in it takes no room, and nothing here is written by hand.
--}}
@php
    use App\Models\ClinicSession;
    use App\Models\Department;
    use App\Models\SitePage;
    use App\Support\SiteSettings;

    $name = SiteSettings::name();
    $motto = SiteSettings::get('site.motto');
    $address = SiteSettings::get('contact.address');
    $phone = SiteSettings::get('contact.phone');
    $emergency = SiteSettings::get('contact.emergency_phone');
    $email = SiteSettings::get('contact.email');
    $logo = SiteSettings::logoUrl();
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
    $lines = fn (?string $text) => array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $text) ?: [])));
    // "Quality: a service that works" => ['Quality', 'a service that works']; a line with no colon is all words.
    $pair = fn (string $line) => preg_match('/^([^:：]{1,40})[:：]\s*(.+)$/u', $line, $found) ? [$found[1], $found[2]] : [null, $line];
    $plain = fn (string $html, int $length) => Str::limit(trim((string) preg_replace('/[\s\x{00a0}]+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5))), $length);

    $vision = $lines(SiteSettings::get('identity.vision'));
    $mission = $lines(SiteSettings::get('identity.mission'));
    $values = $lines(SiteSettings::get('identity.values'));
    // "T — Teamwork": a letter and what it stands for. When every line is one, the letters are set as tiles that spell the word.
    $letters = array_map(fn (string $line) => preg_match('/^(\p{L})\s*[—–\-]\s*(.+)$/u', $line, $found) ? [$found[1], $found[2]] : null, $values);
    $spelt = $values !== [] && ! in_array(null, $letters, true);

    $pages = SitePage::published()->where('section', 'about')->orderBy('position')->orderBy('id')->get()->keyBy(fn (SitePage $page) => route('pages.show', $page));
    // The hospital's own story, when a page tells it: the start of it is shown here, the rest on its page.
    $story = $pages->first(fn (SitePage $page) => preg_match('/ประวัติ.*โรงพยาบาล|history of the hospital|^history$/iu', $page->title.' '.$page->slug));
    // How it begins, without its own title said again at the head of it.
    $opening = $story ? trim(Str::after($plain($story->inLanguage('body'), 700), $story->localTitle())) : '';
    $hours = $lines(SiteSettings::get('contact.hours'));
    $departments = Department::shown()->get();
    $sessions = ClinicSession::shown()->get();
    $about = collect(App\Support\Menus::items('all'))->firstWhere('key', 'about');

    // A mark for each line: the site's own places by where they lead, a page of text by a word in its name.
    $mark = fn (array $child) => match (true) {
        $child['url'] === route('schedule') => 'calendar-days',
        $child['url'] === route('departments.index') => 'building-office-2',
        $child['url'] === route('contact') => 'map-pin',
        Route::has('services') && $child['url'] === route('services') => 'computer-desktop',
        Route::has('faq') && $child['url'] === route('faq') => 'question-mark-circle',
        (bool) preg_match('/ประวัติ|history/iu', $child['label']) => 'clock',
        (bool) preg_match('/วิสัยทัศน์|พันธกิจ|vision|mission/iu', $child['label']) => 'eye',
        (bool) preg_match('/ค่านิยม|values/iu', $child['label']) => 'heart',
        (bool) preg_match('/ยุทธศาสตร์|เข็มมุ่ง|strateg/iu', $child['label']) => 'flag',
        (bool) preg_match('/โครงสร้าง|structure/iu', $child['label']) => 'rectangle-group',
        (bool) preg_match('/ผู้อำนวยการ|กรรมการ|บริหาร|director|board/iu', $child['label']) => 'user-group',
        (bool) preg_match('/แผนผัง|แผนที่|map/iu', $child['label']) => 'map',
        default => 'document-text',
    };

    // Figures counted from the site itself, each left out while it would say "0".
    $figures = array_filter([
        ['Departments', $departments->count(), 'building-office-2'],
        ['Clinics and services', $sessions->count(), 'heart'],
        ['Open 24 hours', $sessions->filter(fn (ClinicSession $session) => $session->starts_at === '00:00' && in_array($session->ends_at, ['23:59', '24:00', '00:00'], true))->count(), 'clock'],
        ['Announcements published', App\Models\Announcement::published()->count(), 'megaphone'],
    ], fn (array $figure) => $figure[1] > 0);

    // The parts that are on the page, for the row of links under the top.
    $parts = array_filter([
        'identity' => $vision || $mission || $values ? 'What we stand for' : null,
        'story' => $story ? 'Our story' : null,
        'hours' => $hours || $sessions->isNotEmpty() ? 'When we are open' : null,
        'departments' => $departments->isNotEmpty() ? 'Departments' : null,
        'more' => 'More about us',
        'reach' => 'Reach us',
    ]);
@endphp
<x-layouts.site :title="__('About us')" :description="__('About :name: its history, vision, structure, departments and how to reach it.', ['name' => $name])" :image="SiteSettings::heroUrl()">
    {{-- 1. Who it is. --}}
    <section class="home-screen !min-h-0 border-b border-line">
        @if ($photo = SiteSettings::heroUrl())
            {{-- The hospital itself, faded behind the words. Decoration: it says nothing a reader needs. --}}
            <img src="{{ $photo }}" alt="" aria-hidden="true" class="hero-photo" fetchpriority="high">
            <div class="hero-veil" aria-hidden="true"></div>
        @endif
        <div class="wrap max-w-5xl py-14 text-center sm:py-20">
            @if ($logo)
                <img src="{{ $logo }}" alt="" class="mx-auto mb-6 h-24 w-auto">
            @else
                <span class="mx-auto mb-6 flex size-20 items-center justify-center rounded-2xl bg-brand text-on-brand shadow-lg" aria-hidden="true"><x-heroicon-s-plus class="size-10" /></span>
            @endif
            <p class="eyebrow">{{ __('About us') }}</p>
            <h1 class="hero-name mt-3 text-4xl leading-tight sm:text-5xl md:text-6xl">{{ $name }}</h1>
            @if ($motto)
                <p class="mx-auto mt-5 max-w-2xl text-xl text-muted sm:text-2xl">“{{ $motto }}”</p>
            @endif
            @if ($address)
                <p class="mt-5 inline-flex items-start gap-2 text-muted"><x-heroicon-m-map-pin class="mt-1 size-5 shrink-0 text-brand" aria-hidden="true" /><span>{{ $address }}</span></p>
            @endif

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @if ($phone)
                    <a href="{{ $dial($phone) }}" class="btn btn-brand"><x-heroicon-s-phone class="size-5" aria-hidden="true" />{{ __('Call :number', ['number' => $phone]) }}</a>
                @endif
                <a href="{{ route('schedule') }}" class="btn btn-line"><x-heroicon-m-calendar-days class="size-5" aria-hidden="true" />{{ __('Clinic schedule') }}</a>
                <a href="{{ route('contact') }}" class="btn btn-line"><x-heroicon-m-map-pin class="size-5" aria-hidden="true" />{{ __('Map and directions') }}</a>
            </div>

            @if ($figures)
                <dl @class(['mx-auto mt-12 grid max-w-3xl gap-3', 'grid-cols-2', 'sm:grid-cols-4' => count($figures) === 4, 'sm:grid-cols-3' => count($figures) === 3])>
                    @foreach ($figures as [$label, $figure, $icon])
                        <div class="card px-3 py-5">
                            <x-dynamic-component :component="'heroicon-o-'.$icon" class="mx-auto size-6 text-brand" aria-hidden="true" />
                            <dd class="mt-2 font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($figure) }}</dd>
                            <dt class="mt-1 text-sm text-muted">{{ __($label) }}</dt>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    </section>

    {{-- Every part one press away; it stays under the top bar as the page is read. On a phone the row slides sideways. --}}
    <nav class="sticky top-16 z-30 border-b border-line bg-page/90 backdrop-blur" aria-label="{{ __('Parts of this page') }}">
        <ul class="wrap flex max-w-5xl gap-2 overflow-x-auto py-3 [scrollbar-width:none]">
            @foreach ($parts as $anchor => $label)
                <li class="shrink-0"><a href="#{{ $anchor }}" class="chip whitespace-nowrap">{{ __($label) }}</a></li>
            @endforeach
        </ul>
    </nav>

    {{-- 2. What it stands for. --}}
    @if ($vision || $mission || $values)
        <section id="identity" class="scroll-mt-32 bg-surface">
            <div class="wrap max-w-5xl py-16 sm:py-20">
                <p class="eyebrow text-center">{{ __('What we stand for') }}</p>

                @if ($vision)
                    <figure class="mx-auto mt-4 max-w-3xl text-center">
                        <figcaption class="font-mono text-sm text-syn-function">{{ __('Vision') }}</figcaption>
                        <blockquote class="mt-3 text-2xl font-medium leading-snug sm:text-4xl">“{{ $vision[0] }}”</blockquote>
                    </figure>
                    @if (count($vision) > 1)
                        {{-- What each word of the vision means. --}}
                        <dl class="mx-auto mt-10 grid max-w-4xl gap-3 sm:grid-cols-2">
                            @foreach (array_slice($vision, 1) as $line)
                                @php([$term, $meaning] = $pair($line))
                                <div class="card bg-page">
                                    @if ($term)
                                        <dt class="font-semibold text-brand">{{ $term }}</dt>
                                    @endif
                                    <dd @class(['mt-1' => $term])>{{ $meaning }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                @endif

                <div @class(['mt-14 grid gap-10', 'lg:grid-cols-2' => $mission && $values])>
                    @if ($mission)
                        <div>
                            <h2 class="text-2xl">{{ __('Mission') }}</h2>
                            <ol class="mt-5 space-y-3">
                                @foreach ($mission as $number => $item)
                                    <li class="flex gap-4 rounded-lg border border-line bg-page px-4 py-4">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand font-mono font-semibold text-on-brand" aria-hidden="true">{{ $number + 1 }}</span>
                                        <span class="pt-1">{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

                    @if ($values)
                        <div>
                            <h2 class="text-2xl">{{ __('Core values') }}</h2>
                            @if ($spelt)
                                {{-- The letters as tiles, so the word they spell is seen before the list is read. --}}
                                <p class="mt-5 flex flex-wrap gap-1.5 sm:gap-2" aria-hidden="true">
                                    @foreach ($letters as [$letter])
                                        <span class="flex size-10 items-center justify-center rounded-lg bg-brand font-mono text-xl font-bold text-on-brand sm:size-12">{{ mb_strtoupper($letter) }}</span>
                                    @endforeach
                                </p>
                                <ul class="mt-5 grid gap-2 sm:grid-cols-2">
                                    @foreach ($letters as [$letter, $meaning])
                                        <li class="flex items-center gap-3 rounded-lg border border-line bg-page px-4 py-3">
                                            <span class="font-mono text-xl font-bold text-brand">{{ mb_strtoupper($letter) }}</span>
                                            <span>{{ $meaning }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <ul class="mt-5 space-y-2">
                                    @foreach ($values as $item)
                                        <li class="flex gap-3 rounded-lg border border-line bg-page px-4 py-3"><x-heroicon-m-check class="mt-1 size-5 shrink-0 text-brand" aria-hidden="true" /><span>{{ $item }}</span></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- 3. How it began: the start of the page that tells it, and the way to the rest. --}}
    @if ($story)
        <section id="story" class="scroll-mt-32">
            <div class="wrap grid max-w-5xl items-start gap-10 py-16 sm:py-20 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <p class="eyebrow">{{ __('Our story') }}</p>
                    <h2 class="mt-2 text-3xl sm:text-4xl">{{ $story->localTitle() }}</h2>
                </div>
                <div class="lg:col-span-3">
                    <p class="border-l-4 border-brand pl-5 text-lg leading-relaxed">{{ Str::limit($opening, 520) }}</p>
                    <a href="{{ route('pages.show', $story) }}" class="btn btn-line mt-6">{{ __('Read the whole story') }} &rarr;</a>
                </div>
            </div>
        </section>
    @endif

    {{-- 4. When it is open: what the hospital says of its hours, beside what the clinic schedule holds for today. --}}
    @if ($hours || $sessions->isNotEmpty())
        <section id="hours" class="scroll-mt-32 bg-surface">
            <div class="wrap max-w-5xl py-16 sm:py-20">
                <p class="eyebrow">{{ __('When we are open') }}</p>
                <h2 class="mt-2 text-3xl sm:text-4xl">{{ __('Opening hours') }}</h2>

                <div @class(['mt-8 grid items-start gap-6', 'lg:grid-cols-2' => $hours && $sessions->isNotEmpty()])>
                    @if ($hours)
                        <dl class="space-y-3">
                            @foreach ($hours as $line)
                                @php([$term, $meaning] = $pair($line))
                                <div class="flex gap-4 rounded-lg border border-line bg-page px-4 py-4">
                                    <x-heroicon-o-clock class="mt-0.5 size-6 shrink-0 text-brand" aria-hidden="true" />
                                    <div>
                                        @if ($term)
                                            <dt class="font-semibold">{{ $term }}</dt>
                                        @endif
                                        <dd class="text-muted">{{ $meaning }}</dd>
                                    </div>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    @if ($sessions->isNotEmpty())
                        @php($today = ClinicSession::today())
                        <div class="code-block">
                            <div class="code-header">
                                <span class="code-dot bg-danger" aria-hidden="true"></span>
                                <span class="code-dot bg-syn-string" aria-hidden="true"></span>
                                <span class="code-dot bg-syn-function" aria-hidden="true"></span>
                                <h3 class="ml-3 text-sm font-medium text-muted">{{ __('Open today') }} · {{ __(ClinicSession::DAYS[now()->dayOfWeek]) }}</h3>
                            </div>
                            @if ($today->isEmpty())
                                <p class="px-4 py-4 text-muted">{{ __('No clinics on this day.') }}</p>
                            @else
                                <ul class="px-4 py-2">
                                    @foreach ($today->take(8) as $session)
                                        <li class="flex items-baseline gap-3 border-b border-line py-2 last:border-b-0">
                                            <span class="w-28 shrink-0 font-mono text-sm text-syn-string">{{ $session->hours() }}</span>
                                            <span class="min-w-0 flex-1">{{ $session->localName() }}</span>
                                            @if ($session->isOpenNow())
                                                <span class="shrink-0 font-mono text-xs font-semibold text-syn-function">{{ __('Open now') }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <p class="border-t border-line px-4 py-3 text-sm"><a href="{{ route('schedule') }}" class="font-semibold text-brand underline">{{ __('The whole week') }} &rarr;</a></p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- 5. Its departments, each leading to a profile of its own. --}}
    @if ($departments->isNotEmpty())
        <section id="departments" class="scroll-mt-32">
            <div class="wrap max-w-5xl py-16 sm:py-20">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="eyebrow">{{ __('Departments') }}</p>
                        <h2 class="mt-2 text-3xl sm:text-4xl">{{ __('The people who look after you') }}</h2>
                    </div>
                    <a href="{{ route('phones') }}" class="btn btn-line"><x-heroicon-m-phone class="size-4" aria-hidden="true" />{{ __('Phone numbers') }}</a>
                </div>
                <ul class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($departments as $department)
                        <li>
                            <a href="{{ route('departments.show', $department) }}" class="card flex h-full items-center gap-4 px-4 py-4 text-ink no-underline">
                                <x-site.department-avatar :department="$department" class="size-12 text-xl" />
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium">{{ $department->localName() }}</span>
                                    @if ($department->location)
                                        <span class="block truncate text-sm text-muted">{{ $department->location }}</span>
                                    @endif
                                </span>
                                <x-heroicon-m-chevron-right class="size-5 shrink-0 text-brand" aria-hidden="true" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- 6. Everything filed under "About us": each page with how it begins, then the site's own places and the administrator's links. --}}
    <section id="more" class="scroll-mt-32 bg-surface">
        <div class="wrap max-w-5xl py-16 sm:py-20">
            <p class="eyebrow">{{ __('More about us') }}</p>
            <h2 class="mt-2 text-3xl sm:text-4xl">{{ __('Read on') }}</h2>

            <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($about['children'] ?? [] as $child)
                    @php($page = $pages->get($child['url']))
                    <li>
                        <a href="{{ $child['url'] }}" class="card flex h-full flex-col bg-page text-ink no-underline"
                           @if (parse_url($child['url'], PHP_URL_HOST) !== request()->getHost()) target="_blank" rel="noopener noreferrer" @endif>
                            <span class="door-mark" aria-hidden="true"><x-dynamic-component :component="'heroicon-o-'.$mark($child)" class="size-5" /></span>
                            <span class="mt-4 block text-lg font-medium">{{ $child['label'] }}</span>
                            @if ($page && ($start = $plain($page->inLanguage('body'), 110)) !== '')
                                <span class="mt-1 line-clamp-3 block flex-1 text-sm text-muted">{{ $start }}</span>
                            @else
                                <span class="flex-1"></span>
                            @endif
                            <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand">{{ __('Open') }}<x-heroicon-m-arrow-right class="size-4" aria-hidden="true" /></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 7. How to reach it. --}}
    <section id="reach" class="scroll-mt-32">
        <div class="wrap max-w-5xl py-16 text-center sm:py-20">
            <p class="eyebrow">{{ __('Reach us') }}</p>
            <h2 class="mt-2 text-3xl sm:text-4xl">{{ __('We are here when you need us') }}</h2>

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

            @if ($address || $email)
                <p class="mx-auto mt-6 max-w-2xl text-muted">
                    {{ $address }}
                    @if ($email)
                        <br><a href="mailto:{{ $email }}" class="font-semibold text-brand underline">{{ $email }}</a>
                    @endif
                </p>
            @endif

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('contact') }}" class="btn btn-brand"><x-heroicon-m-map-pin class="size-5" aria-hidden="true" />{{ __('Map and directions') }}</a>
                <a href="{{ route('phones') }}" class="btn btn-line">{{ __('Phone numbers of every department') }}</a>
            </div>
        </div>
    </section>
</x-layouts.site>
