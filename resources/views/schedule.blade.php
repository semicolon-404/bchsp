{{--
    /schedule: what is open on each day of the week (App\Models\ClinicSession). The band at the top says how much
    there is and how much of it is open this minute; then today, set apart, since that is what most readers came
    for; then a row of the days, which stays in reach as the page is read; then the week, Monday first.
--}}
@php
    use App\Models\ClinicSession;

    $today = now()->dayOfWeek;
    // Read once and dealt out to the days: asking again for each day was seven of the same question.
    $sessions = ClinicSession::shown()->with('department')->get();
    $week = collect(ClinicSession::DAYS)->map(fn (string $name, int $day) => $sessions->filter(fn (ClinicSession $session) => $session->isHeldOn($day))->values());
    $now = $week[$today]->filter->isOpenNow();
    $line = 'flex flex-col gap-1 border-b border-line px-5 py-3 last:border-b-0 sm:flex-row sm:items-center sm:gap-4';
@endphp
<x-layouts.site :title="__('Clinic schedule')" :description="__('When each clinic and service of :name is open during the week.', ['name' => App\Support\SiteSettings::name()])">
    <x-site.page-header :eyebrow="__('Clinic schedule')" :title="__('Clinics and opening hours')" icon="calendar-days" width="max-w-5xl"
                        :lead="__('Hours can change on public holidays. Phone before a long journey.')">
        @if ($sessions->isNotEmpty())
            <dl class="flex flex-wrap gap-x-10 gap-y-4">
                <div>
                    <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($sessions->count()) }}</dd>
                    <dt class="text-sm text-muted">{{ __('Clinics and services') }}</dt>
                </div>
                <div>
                    <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($week[$today]->count()) }}</dd>
                    <dt class="text-sm text-muted">{{ __('Open today') }}</dt>
                </div>
                <div>
                    <dd class="font-mono text-3xl font-semibold text-syn-function" data-count>{{ number_format($now->count()) }}</dd>
                    <dt class="text-sm text-muted">{{ __('Open now') }}</dt>
                </div>
            </dl>
        @endif
    </x-site.page-header>

    @if ($sessions->isEmpty())
        <div class="wrap max-w-5xl py-10 sm:py-14">
            <x-site.empty icon="calendar-days" />
        </div>
    @else
        {{-- The days, one press away; the row stays under the top bar as the page is read. --}}
        <nav class="sticky top-16 z-30 border-b border-line bg-page/90 backdrop-blur" aria-label="{{ __('Days of the week') }}">
            <ul class="wrap flex max-w-5xl gap-2 overflow-x-auto py-3 [scrollbar-width:none]">
                @foreach ($week as $day => $held)
                    <li class="shrink-0">
                        <a href="#day-{{ $day }}" @class(['chip gap-2 whitespace-nowrap', 'is-on' => $day === $today])>
                            {{ __(ClinicSession::DAYS[$day]) }}
                            <span class="font-mono text-xs text-muted">{{ $held->count() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="wrap max-w-5xl py-10 sm:py-14">
            <div class="space-y-5">
                @foreach ($week as $day => $held)
                    <section id="day-{{ $day }}" @class(['card scroll-mt-36 p-0', 'border-brand shadow-lg' => $day === $today]) aria-labelledby="day-heading-{{ $day }}">
                        <h2 id="day-heading-{{ $day }}" @class(['flex flex-wrap items-center gap-3 border-b border-line px-5 py-4 text-xl', 'bg-raised' => $day === $today])>
                            <x-heroicon-o-calendar-days @class(['size-6', 'text-brand' => $day === $today, 'text-muted' => $day !== $today]) aria-hidden="true" />
                            {{ __(ClinicSession::DAYS[$day]) }}
                            @if ($day === $today)
                                <span class="rounded bg-brand px-2 py-0.5 font-mono text-xs font-semibold text-on-brand">{{ __('Today') }}</span>
                            @endif
                            <span class="ml-auto font-mono text-sm font-normal text-muted">{{ trans_choice('{0} |{1} :count clinic|[2,*] :count clinics', $held->count(), ['count' => $held->count()]) }}</span>
                        </h2>
                        @if ($held->isEmpty())
                            <p class="px-5 py-4 text-muted">{{ __('No clinics on this day.') }}</p>
                        @else
                            <ul>
                                @foreach ($held as $session)
                                    <li class="{{ $line }}">
                                        <span class="shrink-0 font-mono text-syn-string sm:w-36">{{ $session->hours() }}</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="font-medium">{{ $session->localName() }}</span>
                                            @if ($session->note)
                                                <span class="block text-sm text-muted">{{ $session->note }}</span>
                                            @endif
                                        </span>
                                        @if ($day === $today && $session->isOpenNow())
                                            {{-- Said in words as well as by the dot. --}}
                                            <span class="inline-flex shrink-0 items-center gap-2 font-mono text-xs font-semibold text-syn-function"><span class="size-2 animate-pulse-dot rounded-full bg-syn-function" aria-hidden="true"></span>{{ __('Open now') }}</span>
                                        @endif
                                        @if ($session->department?->is_active)
                                            <a href="{{ route('departments.show', $session->department) }}" class="inline-flex shrink-0 items-center gap-2 text-sm text-brand underline">
                                                <x-site.department-avatar :department="$session->department" class="size-7 text-xs" />{{ $session->department->localName() }}
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endforeach
            </div>

            <p class="mt-10 flex flex-wrap gap-3">
                <a href="{{ route('departments.index') }}" class="btn btn-line"><x-heroicon-m-building-office-2 class="size-4" aria-hidden="true" />{{ __('All departments') }}</a>
                <a href="{{ route('phones') }}" class="btn btn-line"><x-heroicon-m-phone class="size-4" aria-hidden="true" />{{ __('Phone numbers') }}</a>
            </p>
        </div>
    @endif
</x-layouts.site>
