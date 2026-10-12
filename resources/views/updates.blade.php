{{--
    /updates: what has changed on the website, release by release, newest first (App\Models\SiteUpdate; written in
    the panel under System > Website updates). What is being worked on comes first, apart, with no day promised.
    Drawn as the log of an editor: a number and a day down the side, the changes beside them.
--}}
@php
    use App\Models\SiteUpdate;

    $planned = SiteUpdate::planned()->get();
    $released = SiteUpdate::released()->get();
    $newest = $released->first();

    // This month in figures, counted from what is on the site: it keeps itself up to date.
    $month = App\Models\Announcement::published()->where('starts_at', '>=', now()->startOfMonth());
    $posted = (clone $month)->count();
    $posting = (clone $month)->whereNotNull('department_id')->distinct()->count('department_id');
    $opened = App\Support\Visits::totals()['month'];
@endphp
<x-layouts.site :title="__('Website updates')" :description="__('What is new on the website of :name, and what is being worked on.', ['name' => App\Support\SiteSettings::name()])">
    <div class="wrap max-w-4xl py-10 sm:py-14">
        <p class="eyebrow">{{ __('Website updates') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ __('What is new on this website') }}</h1>
        <p class="mt-3 text-muted">{{ __('This website is looked after and added to all the time. Each change is written down here.') }}</p>

        @if ($newest)
            {{-- The figures at a glance. --}}
            <dl class="mt-8 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['Newest release', $newest->version],
                    ['Last updated', $newest->releasedOn()],
                    ['Releases so far', number_format($released->count())],
                ] as [$label, $figure])
                    <div class="card">
                        <dt class="text-sm text-muted">{{ __($label) }}</dt>
                        <dd class="mt-1 font-mono text-2xl text-brand" @if ($label === 'Releases so far') data-count @endif>{{ $figure }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif

        <section class="mt-10" aria-labelledby="month-heading">
            <h2 id="month-heading" class="text-2xl">{{ __('This month on the site') }}</h2>
            <dl class="mt-4 grid grid-cols-3 gap-3">
                @foreach ([
                    ['Announcements posted', $posted],
                    ['Departments that posted', $posting],
                    ['Pages opened', $opened],
                ] as [$label, $figure])
                    <div class="card px-3 py-4 text-center">
                        <dd class="font-mono text-2xl text-brand" data-count>{{ number_format($figure) }}</dd>
                        <dt class="mt-1 text-sm text-muted">{{ __($label) }}</dt>
                    </div>
                @endforeach
            </dl>
        </section>

        @if ($planned->isNotEmpty())
            <section class="mt-10" aria-labelledby="planned-heading">
                <h2 id="planned-heading" class="text-2xl">{{ __('Being worked on') }}</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($planned as $update)
                        <li class="card border-dashed">
                            <p class="font-medium">{{ $update->localTitle() }}</p>
                            @if ($changes = $update->changes())
                                <ul class="mt-2 list-disc space-y-1 pl-5 text-muted">
                                    @foreach ($changes as $change)
                                        <li>{{ $change['text'] }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="mt-10" aria-labelledby="released-heading">
            <h2 id="released-heading" class="text-2xl">{{ __('Releases') }}</h2>

            @forelse ($released as $update)
                <article class="code-block mt-4" aria-labelledby="release-{{ $update->id }}">
                    <div class="code-header">
                        <span class="rounded border border-brand px-2 font-mono text-sm font-semibold text-brand">v{{ $update->version }}</span>
                        <h3 id="release-{{ $update->id }}" class="min-w-0 flex-1 text-base font-medium">{{ $update->localTitle() }}</h3>
                        <time datetime="{{ $update->released_on->toDateString() }}" class="shrink-0 font-mono text-sm text-muted">{{ $update->releasedOn() }}</time>
                    </div>
                    <ul>
                        @foreach ($update->changes() as $change)
                            <li class="flex items-start gap-3 border-b border-line px-4 py-3 last:border-b-0">
                                {{-- The kind is written out as well as coloured. --}}
                                <span class="mt-0.5 w-20 shrink-0 font-mono text-xs font-semibold uppercase" style="color: {{ $change['colour'] }}">{{ __($change['label']) }}</span>
                                <span class="min-w-0 flex-1">{{ $change['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @empty
                <x-site.empty class="mt-4" icon="sparkles" />
            @endforelse
        </section>

        <p class="mt-10 text-muted">{{ __('Something missing, or not working as it should?') }}
            <a href="{{ route('contact') }}" class="font-semibold text-brand underline">{{ __('Tell us') }}</a>
        </p>
    </div>
</x-layouts.site>
