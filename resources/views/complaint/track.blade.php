{{--
    /complaint/track: following a matter already sent (ComplaintController::track and lookup). Two boxes, the
    tracking number and the six-digit code given when it was sent; with both right, how the matter stands is
    shown under them: where it is on the line from "received" to its end, the day by which it should be dealt
    with, and what the hospital wrote back. Nothing of who sent it is shown: whoever holds the two can read
    the result, and no more.

    Beside it, what each state means and what to do when the number or the code is lost.
--}}
@php
    use App\Models\Complaint;
    use App\Support\SiteSettings;

    $days = SiteSettings::complaintDays();
    $phone = SiteSettings::get('contact.phone');
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
    $day = fn ($date) => $date->locale(app()->getLocale())->translatedFormat('j F ').($date->year + (app()->getLocale() === 'th' ? 543 : 0));

    // The states a matter goes through, in order; the last two are how it ends.
    $states = [
        'received' => ['inbox-arrow-down', 'Your matter has arrived and waits for staff to look at it.', 'muted'],
        'checking' => ['magnifying-glass', 'Staff are checking the first facts.', 'muted'],
        'working' => ['arrow-path', 'It has been passed to the group of work responsible.', 'brand'],
        'done' => ['check-circle', 'It has been dealt with: what was done is written below.', 'brand'],
        'rejected' => ['x-circle', 'It is outside what can be looked into: the reason is written below.', 'danger'],
    ];
    $order = array_keys($states);
@endphp
<x-layouts.site :title="__('Follow a complaint')" :description="__('Look up where a complaint or suggestion sent to :name stands, with its tracking number and code.', ['name' => SiteSettings::name()])">
    <x-site.page-header :eyebrow="__('Complaints and suggestions')" :title="__('Follow a matter already sent')" icon="magnifying-glass-circle" width="max-w-5xl"
                        :lead="__('See where your matter stands and what was done about it, at any hour.')">
        <p class="text-sm"><a href="{{ route('complaint') }}" class="font-semibold text-brand underline">&larr; {{ __('Complaints and suggestions') }}</a></p>
    </x-site.page-header>

    <div class="wrap max-w-5xl py-10 sm:py-14">
        <div class="grid items-start gap-10 lg:grid-cols-5">
            <div class="min-w-0 space-y-8 lg:col-span-3">
                {{-- The two boxes. --}}
                <section class="card ticket" aria-labelledby="look-heading">
                    <h2 id="look-heading" class="text-2xl">{{ __('Look up my matter') }}</h2>
                    <p class="mt-1 text-muted">{{ __('Type the tracking number and the six-digit code you were given when you sent it.') }}</p>

                    <form method="POST" action="{{ route('complaint.lookup') }}" class="form mt-6 space-y-5" novalidate>
                        @csrf
                        <div class="grid gap-5 sm:grid-cols-5">
                            <div class="sm:col-span-3">
                                <label for="t-code" class="field-label">{{ __('Tracking number') }}</label>
                                <input id="t-code" name="code" type="text" maxlength="20" autocomplete="off" autocapitalize="characters" spellcheck="false" required
                                       value="{{ old('code', $complaint?->code) }}" placeholder="CMP-690913-AB7K" class="field font-mono uppercase tracking-wide" @error('code') aria-invalid="true" aria-describedby="t-error" @enderror>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="t-pin" class="field-label">{{ __('Code') }}</label>
                                <input id="t-pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="off" required
                                       placeholder="{{ __('6 digits') }}" class="field font-mono tracking-[0.3em]" @error('pin') aria-invalid="true" @enderror>
                            </div>
                        </div>
                        @error('code')<p id="t-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                        @error('pin')<p class="field-error" role="alert">{{ $message }}</p>@enderror

                        <button type="submit" class="btn btn-brand min-h-12 w-full justify-center text-base"><x-heroicon-m-magnifying-glass class="size-5" aria-hidden="true" />{{ __('Look it up') }}</button>
                        <p class="text-sm text-muted">{{ __('To keep matters safe only a few tries are allowed: after several wrong ones you must wait a little.') }}</p>
                    </form>
                </section>

                {{-- How it stands. --}}
                @if ($complaint)
                    @php
                        $at = array_search($complaint->status, $order, true);
                        $rejected = $complaint->status === 'rejected';
                        // The line it has come along: the three steps every matter takes, then how this one ended (or will).
                        $line = $rejected ? ['received', 'checking', 'rejected'] : ['received', 'checking', 'working', 'done'];
                    @endphp
                    <section id="result" class="card" aria-labelledby="result-heading" tabindex="-1">
                        <p class="eyebrow">{{ __('Result') }}</p>
                        <h2 id="result-heading" class="mt-2 text-2xl">{{ $complaint->title }}</h2>
                        <p class="mt-2 flex flex-wrap gap-x-5 gap-y-1 font-mono text-sm text-muted">
                            <span>{{ $complaint->code }}</span>
                            <span>{{ __(Complaint::KINDS[$complaint->kind] ?? 'Anything else') }}</span>
                            <span>{{ __('Sent on :date', ['date' => $day($complaint->created_at)]) }}</span>
                        </p>

                        <p class="mt-5 inline-flex items-center gap-2 rounded-full border px-4 py-2 font-semibold {{ $rejected ? 'border-danger text-danger' : 'border-brand text-brand' }}">
                            <x-dynamic-component :component="'heroicon-m-'.$states[$complaint->status][0]" class="size-5" aria-hidden="true" />{{ __(Complaint::STATUSES[$complaint->status]) }}
                        </p>
                        <p class="mt-2 text-muted">{{ __($states[$complaint->status][1]) }}</p>

                        <ol class="progress mt-6" aria-label="{{ __('How far it has come') }}">
                            @foreach ($line as $step)
                                @php($reached = array_search($step, $order, true) <= $at || $step === $complaint->status)
                                <li @class(['is-reached' => $reached, 'is-now' => $step === $complaint->status, 'is-danger' => $step === 'rejected'])>
                                    <span class="progress-dot" aria-hidden="true"></span>
                                    <span>{{ __(Complaint::STATUSES[$step]) }}</span>
                                    @if ($step === $complaint->status)<span class="sr-only"> ({{ __('now') }})</span>@endif
                                </li>
                            @endforeach
                        </ol>

                        @if (filled($complaint->reply))
                            <div class="mt-6 rounded-xl border border-line bg-page p-4">
                                <h3 class="flex items-center gap-2 text-lg"><x-heroicon-o-chat-bubble-left-ellipsis class="size-5 text-brand" aria-hidden="true" />{{ __('What the hospital wrote back') }}</h3>
                                <p class="mt-2 whitespace-pre-line">{{ $complaint->reply }}</p>
                                <x-site.updated :at="$complaint->updated_at" class="mt-3" />
                            </div>
                        @elseif (! $complaint->isClosed())
                            <p class="mt-6 flex items-start gap-3 rounded-xl border border-line bg-page p-4">
                                <x-heroicon-m-clock class="mt-0.5 size-5 shrink-0 text-brand" aria-hidden="true" />
                                <span>{{ __('It should be dealt with by :date. Nothing has been written back yet: look again later.', ['date' => $day($complaint->dueOn())]) }}</span>
                            </p>
                        @endif

                        @if ($complaint->closed_at)
                            <p class="mt-4 font-mono text-sm text-muted">{{ __('Closed on :date', ['date' => $day($complaint->closed_at)]) }}</p>
                        @endif
                    </section>
                @endif
            </div>

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
                    <a href="{{ route('complaint.create') }}" class="btn btn-line mt-4 w-full justify-center">{{ __('Send a matter') }} &rarr;</a>
                </section>
            </aside>
        </div>

        {{-- What each state means, as a line a matter moves along. --}}
        <section class="mt-14" aria-labelledby="states-heading">
            <p class="eyebrow">{{ __('Reading the result') }}</p>
            <h2 id="states-heading" class="mt-2 text-2xl sm:text-3xl">{{ __('What each state means') }}</h2>
            <ol class="track mt-6">
                @foreach ($states as $key => [$icon, $text, $tone])
                    <li class="track-step is-{{ $tone }}">
                        <span class="track-mark" aria-hidden="true"><x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" /></span>
                        <div class="min-w-0">
                            <h3 class="text-lg">{{ __(Complaint::STATUSES[$key]) }}</h3>
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
