{{--
    /calendar: what happens in a month, day by day (AnnouncementController::calendar): the announcements that were
    given the day of what they are about: a mobile unit, a blood drive, a clinic that will be closed. A month to a
    page (?month=2026-11), with the way to the one before and the one after. Each line leads to its announcement.
--}}
@php
    $title = app()->getLocale() === 'th' ? $month->locale('th')->translatedFormat('F ').($month->year + 543) : $month->locale('en')->translatedFormat('F Y');
    $weekday = fn ($day) => $day->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'th' ? 'D' : 'D');
@endphp
<x-layouts.site :title="__('Calendar').' '.$title" :description="__('What is happening at :name, day by day.', ['name' => App\Support\SiteSettings::name()])">
    <x-site.page-header :eyebrow="__('Calendar')" :title="$title" icon="calendar-days" width="max-w-3xl"
                        :lead="__('What happens on which day: mobile units, blood drives, clinics that will be closed.')">
        <div class="flex flex-wrap items-end gap-x-10 gap-y-5">
            <dl>
                <dd class="font-mono text-3xl font-semibold text-ink" data-count>{{ number_format($days->flatten()->count()) }}</dd>
                <dt class="text-sm text-muted">{{ __('On the calendar this month') }}</dt>
            </dl>
        <nav class="flex flex-wrap items-center gap-2" aria-label="{{ __('Months') }}">
            <a href="{{ route('calendar', ['month' => $month->copy()->subMonthNoOverflow()->format('Y-m')]) }}" class="chip gap-1" rel="prev"><x-heroicon-m-chevron-left class="size-4" aria-hidden="true" />{{ __('Month before') }}</a>
            @unless ($month->isSameMonth(now()))
                <a href="{{ route('calendar') }}" class="chip">{{ __('This month') }}</a>
            @endunless
            <a href="{{ route('calendar', ['month' => $month->copy()->addMonthNoOverflow()->format('Y-m')]) }}" class="chip gap-1" rel="next">{{ __('Month after') }}<x-heroicon-m-chevron-right class="size-4" aria-hidden="true" /></a>
        </nav>
        </div>
    </x-site.page-header>

    <div class="wrap max-w-3xl py-10 sm:py-14">
        @if ($days->isEmpty())
            <x-site.empty icon="calendar-days">{{ __('Nothing is on the calendar for this month.') }}</x-site.empty>
        @else
            <ol class="space-y-3">
                @foreach ($days as $date => $events)
                    @php($day = \Illuminate\Support\Carbon::parse($date))
                    <li @class(['card flex gap-4 p-0', 'border-brand' => $day->isToday(), 'opacity-70' => $day->isPast() && ! $day->isToday()])>
                        {{-- The day as a leaf torn from a calendar. --}}
                        <div class="flex w-20 shrink-0 flex-col items-center justify-center border-r border-line px-2 py-3 text-center">
                            <span class="font-mono text-xs uppercase text-muted">{{ $weekday($day) }}</span>
                            <span class="font-mono text-3xl font-semibold text-brand">{{ $day->day }}</span>
                            @if ($day->isToday())
                                <span class="font-mono text-xs font-semibold text-brand">{{ __('Today') }}</span>
                            @endif
                        </div>
                        <ul class="min-w-0 flex-1 py-1">
                            @foreach ($events as $event)
                                <li class="border-b border-line last:border-b-0">
                                    <a href="{{ route('announcements.show', $event) }}" class="group flex items-start gap-3 px-3 py-3 no-underline">
                                        <x-site.type-mark :type="$event->type" class="mt-0.5" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-ink group-hover:text-brand">{{ $event->localTitle() }}</span>
                                            <span class="mt-0.5 flex flex-wrap gap-x-3 font-mono text-xs text-muted">
                                                @if ($event->event_until && ! $event->event_until->isSameDay($event->event_on))
                                                    <span>{{ __('until :date', ['date' => $event->event_until->locale(app()->getLocale())->translatedFormat('j M')]) }}</span>
                                                @endif
                                                @if ($event->department?->is_active)
                                                    <span>{{ $event->department->localName() }}</span>
                                                @endif
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</x-layouts.site>
