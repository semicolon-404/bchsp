{{--
    Under an announcement: "Was this useful?", one press, no account and nothing about the reader kept
    (AnnouncementController::helpful). The browser's session remembers that it was pressed, so the count is not
    run up by pressing again; after the press the button gives way to a thank-you.
--}}
@props(['announcement'])
@php($pressed = in_array($announcement->id, (array) session('helpful', []), true))
<section id="helpful" class="card mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 print:hidden" aria-labelledby="helpful-heading">
    <div class="min-w-0 flex-1">
        <h2 id="helpful-heading" class="text-lg">{{ $pressed ? __('Thank you for telling us.') : __('Was this useful to you?') }}</h2>
        <p class="mt-1 text-sm text-muted">
            @if ($announcement->helpful > 0)
                {{ trans_choice('{1} :count reader found this useful|[2,*] :count readers found this useful', $announcement->helpful, ['count' => number_format($announcement->helpful)]) }}
            @else
                {{ __('One press tells the hospital what is worth writing more of. Nothing about you is kept.') }}
            @endif
        </p>
    </div>
    @unless ($pressed)
        <form method="POST" action="{{ route('announcements.helpful', $announcement) }}">
            @csrf
            <button type="submit" class="btn btn-brand min-h-12"><x-heroicon-m-hand-thumb-up class="size-5" aria-hidden="true" />{{ __('Yes, it was useful') }}</button>
        </form>
    @else
        <span class="inline-flex items-center gap-2 font-semibold text-brand"><x-heroicon-m-check-circle class="size-6" aria-hidden="true" />{{ __('Noted') }}</span>
    @endunless
</section>
