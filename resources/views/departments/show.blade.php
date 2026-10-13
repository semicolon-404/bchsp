{{--
    /departments/{slug}: a unit of the hospital as a profile (DepartmentController::show). Across the top its cover,
    its round picture, its name, whether it is open right now and how to reach it. Under that, what it has posted,
    newest first, beside what it says of itself: what it does, its services, when it is open, where it is.
    "People" (?tab=people) is who works there; "Photographs" (?tab=photos) gathers every picture it has posted.
--}}
@php
    use App\Models\ClinicSession;

    $facts = array_filter(['Location' => $department->location, 'Phone' => $department->phone, 'Email' => $department->email, 'Opening hours' => $department->hours]);
    $links = $department->links();
    $services = $department->serviceList();
    $tabs = array_filter(['posts' => 'Posts', 'people' => $people ? 'Our people' : null, 'photos' => 'Photographs']);
    $about = filled($department->localDescription()) || $facts || $services || $sessions->isNotEmpty();
    // "Mon, Wed, Fri", or "Every day": the days a session is held, Monday first.
    $days = function (ClinicSession $session) {
        $held = array_values(array_filter(array_keys(ClinicSession::DAYS), fn (int $day) => $session->isHeldOn($day)));

        return count($held) === 7
            ? __('Every day')
            : implode(' ', array_map(fn (int $day) => app()->getLocale() === 'th' ? Str::replaceFirst('วัน', '', __(ClinicSession::DAYS[$day])) : mb_substr(__(ClinicSession::DAYS[$day]), 0, 3), $held));
    };
@endphp
<x-layouts.site :title="$department->localName()" :description="Str::limit($department->localHeadline() ?: $department->localDescription(), 160)" :image="$department->imageUrl()">
    <div class="wrap max-w-5xl py-6 sm:py-10">
        <p class="text-sm"><a href="{{ route('departments.index') }}" class="font-semibold text-brand underline">&larr; {{ __('All departments') }}</a></p>

        @unless ($department->is_active)
            {{-- Only an administrator gets this far (DepartmentController::show). --}}
            <p class="mt-6 rounded-lg border border-danger px-4 py-3 text-danger" role="status">{{ __('Not published yet: only administrators can see this page.') }}</p>
        @endunless

        {{-- Cover, identity, ways to reach it and the tabs: one card. --}}
        <header class="card mt-4 overflow-hidden p-0">
            {{-- Three to one, cut to fit. Without a picture, a band of the site's colour is enough. --}}
            @if ($image = $department->imageUrl())
                <a href="{{ $image }}" target="_blank" rel="noopener" class="block aspect-[3/1] bg-raised">
                    <img src="{{ $image }}" alt="" class="size-full object-cover">
                    <span class="sr-only">{{ __('Open the picture at full size') }}</span>
                </a>
            @else
                <div class="h-24 bg-gradient-to-br from-brand/50 to-raised sm:h-36" aria-hidden="true"></div>
            @endif

            <div class="relative flex flex-wrap items-end gap-x-4 gap-y-3 px-5 pb-5">
                <x-site.department-avatar :department="$department" class="relative -mt-12 size-24 border-4 !border-surface text-4xl sm:-mt-14 sm:size-28" />

                {{-- On a phone the name takes a row of its own under the picture. --}}
                <div class="min-w-0 flex-1 basis-full sm:basis-0 sm:pt-3">
                    <p class="eyebrow">{{ __('Departments') }}</p>
                    <h1 class="hero-name mt-1 text-2xl sm:text-4xl">{{ $department->localName() }}</h1>
                    @if (filled($department->localHeadline()))
                        <p class="mt-1 text-muted">{{ $department->localHeadline() }}</p>
                    @endif
                    <p class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-muted">
                        @if ($sessions->isNotEmpty())
                            {{-- Said in words as well as by the dot: colour alone tells nothing to some readers. --}}
                            <span class="inline-flex items-center gap-2 font-medium {{ $openNow ? 'text-syn-function' : '' }}">
                                <span class="size-2 rounded-full {{ $openNow ? 'bg-syn-function' : 'bg-muted' }}" aria-hidden="true"></span>{{ $openNow ? __('Open now') : __('Closed now') }}
                            </span>
                        @endif
                        <span><span class="font-semibold text-ink" data-count>{{ number_format($inAll) }}</span> {{ __('posts') }}</span>
                        <span><span class="font-semibold text-ink" data-count>{{ number_format($photoCount) }}</span> {{ __('photographs') }}</span>
                        @if ($people)
                            <span><span class="font-semibold text-ink" data-count>{{ number_format(count($people)) }}</span> {{ __('people') }}</span>
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($department->dialUrl())
                        <a href="{{ $department->dialUrl() }}" class="btn btn-brand"><x-heroicon-s-phone class="size-4" aria-hidden="true" />{{ __('Call') }}</a>
                    @endif
                    @foreach ($links as $name => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="btn btn-line">{{ $name }}<span class="sr-only"> {{ __('(opens in a new tab)') }}</span></a>
                    @endforeach
                </div>
            </div>

            <nav class="flex flex-wrap gap-2 border-t border-line px-5 py-3" aria-label="{{ __('Parts of this page') }}">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('departments.show', array_filter(['department' => $department, 'tab' => $key === 'posts' ? null : $key])) }}"
                       @class(['chip', 'is-on' => $tab === $key]) aria-current="{{ $tab === $key ? 'page' : 'false' }}">{{ __($label) }}</a>
                @endforeach
            </nav>
        </header>

        @if ($tab === 'photos')
            <section class="mt-6" aria-labelledby="photos-heading">
                <h2 id="photos-heading" class="text-2xl">{{ __('Photographs') }}</h2>
                @if ($photos->isNotEmpty())
                    {{-- Each leads to the post it came from. --}}
                    <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($photos as $photo)
                            <li>
                                <a href="{{ $photo['to'] }}" class="block overflow-hidden rounded-lg border border-line">
                                    <img src="{{ $photo['url'] }}" alt="{{ $photo['title'] }}" loading="lazy" class="aspect-square w-full object-cover">
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-site.empty class="mt-4" icon="photo" />
                @endif
            </section>
        @elseif ($tab === 'people')
            <section class="mt-6" aria-labelledby="people-heading">
                <h2 id="people-heading" class="text-2xl">{{ __('Our people') }}</h2>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($people as $person)
                        <li class="card flex items-center gap-4">
                            @if ($person['photo'])
                                <img src="{{ $person['photo'] }}" alt="" loading="lazy" class="size-16 shrink-0 rounded-full border border-line object-cover">
                            @else
                                <span class="flex size-16 shrink-0 items-center justify-center rounded-full bg-raised text-muted" aria-hidden="true"><x-heroicon-o-user class="size-7" /></span>
                            @endif
                            <div class="min-w-0">
                                <p class="font-medium">{{ $person['name'] }}</p>
                                @if ($person['position'])
                                    <p class="text-sm text-muted">{{ $person['position'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        @else
            <div @class(['mt-6 grid items-start gap-6', 'lg:grid-cols-5' => $about])>
                {{-- What it says of itself: beside the posts on a wide screen, above them on a phone. --}}
                @if ($about)
                    <aside class="space-y-6 lg:col-span-2">
                        @if (filled($department->localDescription()) || $facts)
                            <section class="card" aria-labelledby="intro-heading">
                                <h2 id="intro-heading" class="text-lg">{{ __('About this department') }}</h2>
                                @if (filled($department->localDescription()))
                                    <p class="mt-3 whitespace-pre-line">{{ $department->localDescription() }}</p>
                                @endif
                                @if ($facts)
                                    <dl @class(['space-y-3', 'mt-4 border-t border-line pt-4' => filled($department->localDescription()), 'mt-3' => blank($department->localDescription())])>
                                        @foreach ($facts as $label => $value)
                                            <div>
                                                <dt class="font-mono text-sm text-syn-function">{{ __($label) }}</dt>
                                                <dd class="whitespace-pre-line break-words">
                                                    @if ($label === 'Phone' && $department->dialUrl())
                                                        <a href="{{ $department->dialUrl() }}" class="font-semibold text-brand underline">{{ $value }}</a>
                                                    @elseif ($label === 'Email')
                                                        <a href="mailto:{{ $value }}" class="font-semibold text-brand underline">{{ $value }}</a>
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif
                            </section>
                        @endif

                        @if ($services)
                            <section class="card" aria-labelledby="services-heading">
                                <h2 id="services-heading" class="text-lg">{{ __('Services') }}</h2>
                                <ul class="mt-3 space-y-2">
                                    @foreach ($services as $service)
                                        <li class="flex gap-2"><x-heroicon-m-check class="mt-1 size-4 shrink-0 text-brand" aria-hidden="true" /><span>{{ $service }}</span></li>
                                    @endforeach
                                </ul>
                            </section>
                        @endif

                        @if ($sessions->isNotEmpty())
                            {{-- Its lines of the clinic schedule, with the one going on now marked. --}}
                            <section class="card p-0" aria-labelledby="hours-heading">
                                <h2 id="hours-heading" class="px-5 pt-5 text-lg">{{ __('When it is open') }}</h2>
                                <ul class="mt-3">
                                    @foreach ($sessions as $session)
                                        <li class="border-t border-line px-5 py-3">
                                            <p class="flex flex-wrap items-baseline justify-between gap-x-3">
                                                <span>{{ $session->localName() }}</span>
                                                @if ($session->isOpenNow())
                                                    <span class="font-mono text-xs font-semibold text-syn-function">{{ __('Open now') }}</span>
                                                @endif
                                            </p>
                                            <p class="font-mono text-sm text-syn-string">{{ $days($session) }} · {{ $session->hours() }}</p>
                                            @if ($session->note)
                                                <p class="text-sm text-muted">{{ $session->note }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="border-t border-line px-5 py-3 text-sm"><a href="{{ route('schedule') }}" class="font-semibold text-brand underline">{{ __('The whole week') }} &rarr;</a></p>
                            </section>
                        @endif
                        <x-site.updated :at="$department->updated_at" />
                    </aside>
                @endif

                <section @class(['lg:col-span-3' => $about, 'mx-auto w-full max-w-2xl' => ! $about]) aria-labelledby="board-heading">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <h2 id="board-heading" class="text-2xl">{{ __('Posts') }}</h2>
                        @if ($inAll > 0)
                            <a href="{{ route('announcements.index', ['department' => $department->slug]) }}" class="btn btn-line">{{ __('See all :count', ['count' => number_format($inAll)]) }} &rarr;</a>
                        @endif
                    </div>

                    @if ($posts->isNotEmpty())
                        <ul class="mt-4 space-y-4">
                            @foreach ($posts as $announcement)
                                <x-site.post :announcement="$announcement" :department="$department" />
                            @endforeach
                        </ul>
                    @else
                        <x-site.empty class="mt-4" icon="megaphone">{{ __('This department has not posted anything yet.') }}</x-site.empty>
                    @endif
                </section>
            </div>
        @endif
    </div>
</x-layouts.site>
