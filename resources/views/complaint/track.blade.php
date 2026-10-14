{{--
    /complaint/track: following a matter already sent. The looking-up itself is done on the hospital's own
    system, where the matter is kept (Site settings: "complaint.track_url"); this page says what to have at
    hand, sends the reader there, and explains what each state of a matter means and what to do when the
    tracking number or the code is lost.

    Nothing is typed into this site: the number and the code are asked for by the system that issued them.
--}}
@php
    use App\Support\SiteSettings;

    $track = SiteSettings::complaintUrl('track');
    $days = SiteSettings::complaintDays();
    $phone = SiteSettings::get('contact.phone');
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);

    // The states a matter goes through, in order; the last two are how it ends.
    $states = [
        ['inbox-arrow-down', 'Received', 'Your matter has arrived and waits for staff to look at it.', 'muted'],
        ['magnifying-glass', 'Being checked', 'Staff are checking the first facts.', 'muted'],
        ['arrow-path', 'Being dealt with', 'It has been passed to the group of work responsible.', 'brand'],
        ['check-circle', 'Finished', 'It has been dealt with: what was done is written on the tracking page.', 'brand'],
        ['x-circle', 'Not taken up', 'It is outside what can be looked into: the reason is written on the tracking page.', 'danger'],
    ];
@endphp
<x-layouts.site :title="__('Follow a complaint')" :description="__('Look up where a complaint or suggestion sent to :name stands, with its tracking number and code.', ['name' => SiteSettings::name()])">
    <x-site.page-header :eyebrow="__('Complaints and suggestions')" :title="__('Follow a matter already sent')" icon="magnifying-glass-circle" width="max-w-5xl"
                        :lead="__('See where your matter stands and what was done about it, at any hour.')">
        <p class="text-sm"><a href="{{ route('complaint') }}" class="font-semibold text-brand underline">&larr; {{ __('Complaints and suggestions') }}</a></p>
    </x-site.page-header>

    <div class="wrap max-w-5xl py-10 sm:py-14">
        <div class="grid items-start gap-10 lg:grid-cols-5">
            {{-- What to have at hand, and the way to the looking-up. --}}
            <section class="card ticket lg:col-span-3" aria-labelledby="have-heading">
                <p class="eyebrow">{{ __('Have these two at hand') }}</p>
                <h2 id="have-heading" class="mt-2 text-2xl">{{ __('What you were given when you sent it') }}</h2>
                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-dashed border-line bg-page p-4">
                        <dt class="flex items-center gap-2 text-sm text-muted"><x-heroicon-m-ticket class="size-4 text-brand" aria-hidden="true" />{{ __('Tracking number') }}</dt>
                        <dd class="mt-2 font-mono text-xl font-semibold tracking-wide">CMP-690913-AB7K</dd>
                        <dd class="mt-1 text-xs text-muted">{{ __('An example: yours has its own date and letters.') }}</dd>
                    </div>
                    <div class="rounded-xl border border-dashed border-line bg-page p-4">
                        <dt class="flex items-center gap-2 text-sm text-muted"><x-heroicon-m-key class="size-4 text-brand" aria-hidden="true" />{{ __('Code') }}</dt>
                        <dd class="mt-2 font-mono text-xl font-semibold tracking-[0.35em]">••••••</dd>
                        <dd class="mt-1 text-xs text-muted">{{ __('Six digits, shown once when the matter was sent.') }}</dd>
                    </div>
                </dl>

                @if ($track)
                    <a href="{{ $track }}" target="_blank" rel="noopener noreferrer" class="btn btn-brand mt-6 min-h-12 w-full justify-center text-base">
                        <x-heroicon-m-magnifying-glass class="size-5" aria-hidden="true" />{{ __('Look up my matter') }}<span class="sr-only"> {{ __('(opens in a new tab)') }}</span>
                    </a>
                    <p class="mt-3 text-sm text-muted">{{ __('It opens on the hospital\'s own system, where the matter is kept, in a new tab. To keep matters safe it allows only a few tries: after several wrong ones you must wait a little.') }}</p>
                @else
                    <p class="mt-6 rounded-lg border border-line bg-page px-4 py-3 text-muted">{{ __('Looking up online is not open yet. Please call the hospital with your tracking number.') }}</p>
                @endif
            </section>

            <aside class="space-y-6 lg:col-span-2">
                <section class="card" aria-labelledby="lost-heading">
                    <h2 id="lost-heading" class="flex items-center gap-2 text-lg"><x-heroicon-o-question-mark-circle class="size-5 text-brand" aria-hidden="true" />{{ __('Lost the number or the code?') }}</h2>
                    <p class="mt-2 text-muted">{{ __('The code itself is not kept, so a new one cannot be given on the website. If you gave your name when sending, call the staff and say your name and the day you sent it.') }}</p>
                    @if ($phone)
                        <a href="{{ $dial($phone) }}" class="btn btn-line mt-4 w-full justify-center"><x-heroicon-s-phone class="size-4" aria-hidden="true" />{{ $phone }}</a>
                    @endif
                </section>

                <section class="card" aria-labelledby="new-heading">
                    <h2 id="new-heading" class="text-lg">{{ __('Not sent anything yet?') }}</h2>
                    <p class="mt-2 text-muted">{{ __('A matter can be sent online at any hour.') }}</p>
                    <a href="{{ route('complaint') }}" class="btn btn-line mt-4 w-full justify-center">{{ __('How to send a matter') }} &rarr;</a>
                </section>
            </aside>
        </div>

        {{-- What each state means, as a line a matter moves along. --}}
        <section class="mt-14" aria-labelledby="states-heading">
            <p class="eyebrow">{{ __('Reading the result') }}</p>
            <h2 id="states-heading" class="mt-2 text-2xl sm:text-3xl">{{ __('What each state means') }}</h2>
            <ol class="track mt-6">
                @foreach ($states as [$icon, $title, $text, $tone])
                    <li class="track-step is-{{ $tone }}">
                        <span class="track-mark" aria-hidden="true"><x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" /></span>
                        <div class="min-w-0">
                            <h3 class="text-lg">{{ __($title) }}</h3>
                            <p class="mt-1 text-muted">{{ __($text) }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
            <p class="mt-6 inline-flex items-center gap-2 rounded-lg border border-line bg-surface px-4 py-3">
                <x-heroicon-m-clock class="size-5 shrink-0 text-brand" aria-hidden="true" />
                {{ trans_choice('{1} A matter is dealt with within :count day of being received.|[2,*] A matter is dealt with within :count days of being received.', $days, ['count' => $days]) }}
            </p>
        </section>
    </div>
</x-layouts.site>
