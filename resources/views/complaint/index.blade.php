{{--
    /complaint: how to make a complaint, give a suggestion or report wrongdoing. The page explains the way in
    three steps, says what is taken and how long it takes, and leads to the form (/complaint/send) and to the
    page where a matter already sent is followed (/complaint/track); and gives the other ways (a call, a
    letter, in person) for someone who would rather not use a form.
--}}
@php
    use App\Support\SiteSettings;

    $name = SiteSettings::name();
    $days = SiteSettings::complaintDays();
    $phone = SiteSettings::get('contact.phone');
    $emergency = SiteSettings::get('contact.emergency_phone');
    $email = SiteSettings::get('contact.email');
    $address = SiteSettings::get('contact.address');
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);

    $kinds = [
        ['face-frown', 'Complaint about a service', 'Waiting too long, a mistake, a place that was not fit to use.'],
        ['user-minus', 'Complaint about the conduct of staff', 'Words or manner that were not right.'],
        ['shield-exclamation', 'Report of corruption or misconduct', 'Asking for money, favouring, misuse of what belongs to the hospital.'],
        ['light-bulb', 'Suggestion', 'An idea that would make the hospital better.'],
        ['hand-thumb-up', 'Praise', 'Someone or something that deserves to be told so.'],
        ['ellipsis-horizontal-circle', 'Anything else', 'What fits none of the above.'],
    ];
    $steps = [
        ['pencil-square', 'Send it online', 'Choose what kind it is, tell what happened, attach proof if there is any. Give your name, or stay unnamed.'],
        ['ticket', 'Get a tracking number', 'At once you are given a tracking number and a six-digit code. Keep both: they are the only way back to your matter.'],
        ['magnifying-glass-circle', 'Follow what is done', 'Look up where your matter stands and what was done about it, at any hour, on the tracking page.'],
    ];
@endphp
<x-layouts.site :title="__('Complaints and suggestions')" :description="__('How to make a complaint, give a suggestion or report wrongdoing at :name, and how to follow what is done about it.', ['name' => $name])">
    <x-site.page-header :eyebrow="__('Contact us')" :title="__('Complaints and suggestions')" icon="chat-bubble-left-right" width="max-w-5xl"
                        :lead="__('Tell the hospital what went wrong, what could be better, or what deserves praise. Every matter is read and answered.')">
        <p class="flex flex-wrap gap-3">
            <a href="{{ route('complaint.create') }}" class="btn btn-brand min-h-12 px-6 text-base"><x-heroicon-m-pencil-square class="size-5" aria-hidden="true" />{{ __('Send a matter online') }}</a>
            <a href="{{ route('complaint.track') }}" class="btn btn-line min-h-12 px-6 text-base"><x-heroicon-m-magnifying-glass class="size-5" aria-hidden="true" />{{ __('Follow a matter already sent') }}</a>
        </p>
        <p class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted">
            <span class="inline-flex items-center gap-2"><x-heroicon-m-clock class="size-4 text-brand" aria-hidden="true" />{{ __('About 3 to 5 minutes') }}</span>
            <span class="inline-flex items-center gap-2"><x-heroicon-m-user-circle class="size-4 text-brand" aria-hidden="true" />{{ __('No account needed') }}</span>
            <span class="inline-flex items-center gap-2"><x-heroicon-m-lock-closed class="size-4 text-brand" aria-hidden="true" />{{ __('Kept in confidence') }}</span>
            <span class="inline-flex items-center gap-2"><x-heroicon-m-eye-slash class="size-4 text-brand" aria-hidden="true" />{{ __('You may stay unnamed') }}</span>
        </p>
    </x-site.page-header>

    @if ($emergency)
        {{-- A form is never the way for someone who is ill now. --}}
        <div class="border-b border-danger bg-surface">
            <p class="wrap flex max-w-5xl flex-wrap items-center gap-x-4 gap-y-2 py-3">
                <x-heroicon-m-exclamation-triangle class="size-5 shrink-0 text-danger" aria-hidden="true" />
                <span class="min-w-0 flex-1">{{ __('For an emergency or sudden illness, do not use a form: call.') }}</span>
                <a href="{{ $dial($emergency) }}" class="btn btn-danger"><x-heroicon-s-phone class="size-4" aria-hidden="true" />{{ $emergency }}</a>
            </p>
        </div>
    @endif

    <div class="wrap max-w-5xl py-10 sm:py-14">
        {{-- The way, in three steps, read left to right. --}}
        <section aria-labelledby="steps-heading">
            <h2 id="steps-heading" class="mt-2 text-2xl sm:text-3xl">{{ __('Three steps') }}</h2>
            <ol class="steps mt-6">
                @foreach ($steps as [$icon, $title, $text])
                    <li class="card step">
                        <span class="step-number" aria-hidden="true">{{ $loop->iteration }}</span>
                        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-8 text-brand" aria-hidden="true" />
                        <h3 class="mt-3 text-lg"><span class="sr-only">{{ __('Step :number', ['number' => $loop->iteration]) }}: </span>{{ __($title) }}</h3>
                        <p class="mt-2 text-muted">{{ __($text) }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <div class="mt-14 grid items-start gap-10 lg:grid-cols-3">
            <div class="min-w-0 space-y-14 lg:col-span-2">
                {{-- What is taken through here. --}}
                <section aria-labelledby="kinds-heading">
                    <h2 id="kinds-heading" class="mt-2 text-2xl sm:text-3xl">{{ __('Matters taken through this way') }}</h2>
                    <ul class="mt-6 grid gap-4 sm:grid-cols-2">
                        @foreach ($kinds as [$icon, $title, $text])
                            <li class="card flex items-start gap-4">
                                <span class="door-mark" aria-hidden="true"><x-dynamic-component :component="'heroicon-o-'.$icon" class="size-5" /></span>
                                <span class="min-w-0"><span class="block font-medium">{{ __($title) }}</span><span class="mt-1 block text-sm text-muted">{{ __($text) }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- What a reader should know before writing. --}}
                <section aria-labelledby="notes-heading">
                    <h2 id="notes-heading" class="mt-2 text-2xl sm:text-3xl">{{ __('Good to know') }}</h2>
                    <ul class="mt-6 space-y-3">
                        @foreach ([
                            ['lock-closed', 'What you tell and who you are is kept in confidence: only the staff responsible for the matter can see it.'],
                            ['clipboard-document-check', 'Give facts that can be checked: the day, the time, the place and who was there.'],
                            ['scale', 'A matter with no ground to it, or one a court is already hearing, may not be taken up.'],
                            ['eye-slash', 'Unnamed, you can still follow your matter with the tracking number and the code. Named, staff can call you back to ask more.'],
                        ] as [$icon, $text])
                            <li class="flex items-start gap-3">
                                <x-dynamic-component :component="'heroicon-m-'.$icon" class="mt-1 size-5 shrink-0 text-brand" aria-hidden="true" />
                                <span>{{ __($text) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24">
                {{-- How long it takes: the figure large. --}}
                <section class="card text-center" aria-labelledby="days-heading">
                    <h2 id="days-heading" class="eyebrow">{{ __('How long it takes') }}</h2>
                    <p class="mt-3 font-mono text-5xl font-semibold text-brand">{{ $days }}</p>
                    <p class="font-medium">{{ __('days at most') }}</p>
                    <p class="mt-3 text-sm text-muted">{{ __('Counted from the day the matter is received. Staff check it, pass it to the group responsible, and say what was done on the tracking page.') }}</p>
                    <a href="{{ route('complaint.track') }}" class="btn btn-line mt-4 w-full justify-center">{{ __('Follow a matter already sent') }} &rarr;</a>
                </section>

                {{-- For someone who would rather not use a form. --}}
                <section aria-labelledby="ways-heading">
                    <h2 id="ways-heading" class="flex items-center gap-2 text-xl"><x-heroicon-o-inbox-arrow-down class="size-5 text-brand" aria-hidden="true" />{{ __('Other ways to reach us') }}</h2>
                    <ul class="mt-3 space-y-2">
                        @if ($phone)
                            <li><a href="{{ $dial($phone) }}" class="card flex items-center gap-3 px-4 py-3 text-ink no-underline">
                                <x-heroicon-o-phone class="size-5 shrink-0 text-brand" aria-hidden="true" />
                                <span class="min-w-0"><span class="block text-sm text-muted">{{ __('Phone') }}</span><span class="block font-mono font-semibold">{{ $phone }}</span></span>
                            </a></li>
                        @endif
                        @if ($email)
                            <li><a href="mailto:{{ $email }}" class="card flex items-center gap-3 px-4 py-3 text-ink no-underline">
                                <x-heroicon-o-envelope class="size-5 shrink-0 text-brand" aria-hidden="true" />
                                <span class="min-w-0"><span class="block text-sm text-muted">{{ __('Email') }}</span><span class="block break-all font-medium">{{ $email }}</span></span>
                            </a></li>
                        @endif
                        @if ($address)
                            <li><a href="{{ route('contact') }}" class="card flex items-start gap-3 px-4 py-3 text-ink no-underline">
                                <x-heroicon-o-map-pin class="mt-0.5 size-5 shrink-0 text-brand" aria-hidden="true" />
                                <span class="min-w-0"><span class="block text-sm text-muted">{{ __('In person or by post') }}</span><span class="block">{{ $address }}</span></span>
                            </a></li>
                        @endif
                    </ul>
                </section>
            </aside>
        </div>

        {{-- The way in again, for a reader who has read to the foot. --}}
        <section class="card mt-14 flex flex-wrap items-center justify-between gap-5 border-brand">
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl">{{ __('Ready to tell us?') }}</h2>
                <p class="mt-1 text-muted">{{ __('What you write is kept by the hospital in confidence, and used only to deal with your matter.') }}</p>
            </div>
            <a href="{{ route('complaint.create') }}" class="btn btn-brand min-h-12 px-6 text-base"><x-heroicon-m-pencil-square class="size-5" aria-hidden="true" />{{ __('Send a matter online') }}</a>
        </section>
    </div>
</x-layouts.site>
