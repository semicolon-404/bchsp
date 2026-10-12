{{-- /schedule: what is open on each day of the week, Monday first, with today marked (App\Models\ClinicSession). --}}
@php
    use App\Models\ClinicSession;

    $today = now()->dayOfWeek;
    // Read once and dealt out to the days: asking again for each day was seven of the same question.
    $sessions = ClinicSession::shown()->with('department')->get();
    $week = collect(ClinicSession::DAYS)->map(fn (string $name, int $day) => $sessions->filter(fn (ClinicSession $session) => $session->isHeldOn($day))->values());
@endphp
<x-layouts.site :title="__('Clinic schedule')" :description="__('When each clinic and service of :name is open during the week.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap max-w-4xl py-10 sm:py-14">
        <p class="eyebrow">{{ __('Clinic schedule') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('Clinics and opening hours') }}</h1>
        <p class="mt-3 text-muted">{{ __('Hours can change on public holidays. Phone before a long journey.') }}</p>

        @if ($week->flatten()->isEmpty())
            <x-site.empty class="mt-8" icon="calendar-days" />
        @else
            <div class="mt-8 space-y-4">
                @foreach ($week as $day => $sessions)
                    <section @class(['card p-0', 'border-brand' => $day === $today]) aria-labelledby="day-{{ $day }}">
                        <h2 id="day-{{ $day }}" class="flex items-center gap-3 border-b border-line px-5 py-3 text-lg">
                            {{ __(ClinicSession::DAYS[$day]) }}
                            @if ($day === $today)
                                <span class="rounded border border-brand px-1.5 font-mono text-xs font-semibold text-brand">{{ __('Today') }}</span>
                            @endif
                        </h2>
                        @if ($sessions->isEmpty())
                            <p class="px-5 py-3 text-sm text-muted">{{ __('No clinics on this day.') }}</p>
                        @else
                            <ul>
                                @foreach ($sessions as $session)
                                    <li class="flex flex-col gap-1 border-b border-line px-5 py-3 last:border-b-0 sm:flex-row sm:items-baseline sm:gap-4">
                                        <span class="shrink-0 font-mono text-sm text-syn-string sm:w-32">{{ $session->hours() }}</span>
                                        <span class="min-w-0 flex-1">
                                            {{ $session->localName() }}
                                            @if ($session->note)
                                                <span class="block text-sm text-muted">{{ $session->note }}</span>
                                            @endif
                                        </span>
                                        @if ($session->department?->is_active)
                                            <a href="{{ route('departments.show', $session->department) }}" class="shrink-0 text-sm text-brand underline">{{ $session->department->localName() }}</a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.site>
