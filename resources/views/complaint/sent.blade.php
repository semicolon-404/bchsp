{{--
    /complaint/sent: shown once, straight after a matter is sent (ComplaintController::sent). The tracking number
    and the six-digit code, large, with the ways to keep them: copied, or printed. The code is not kept anywhere
    it could be read again, so the page says plainly that this is the only time it is shown.
--}}
@php($days = App\Support\SiteSettings::complaintDays())
<x-layouts.site :title="__('Your matter was received')">
    <x-site.page-header :eyebrow="__('Complaints and suggestions')" :title="__('Your matter was received')" icon="check-badge" width="max-w-3xl"
                        :lead="__('Thank you for telling us. Keep the two things below: they are the only way back to your matter.')" />

    <div class="wrap max-w-3xl py-10 sm:py-14">
        <section class="card ticket" aria-labelledby="keep-heading">
            <h2 id="keep-heading" class="eyebrow">{{ __('Keep these two') }}</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-dashed border-line bg-page p-5">
                    <dt class="flex items-center gap-2 text-sm text-muted"><x-heroicon-m-ticket class="size-4 text-brand" aria-hidden="true" />{{ __('Tracking number') }}</dt>
                    <dd class="mt-2 select-all font-mono text-2xl font-semibold tracking-wide">{{ $code }}</dd>
                    <dd class="mt-3"><button type="button" class="chip min-h-9 gap-1 px-3 text-sm" data-copy="{{ $code }}" data-copied="{{ __('Copied') }}" hidden><x-heroicon-m-clipboard class="size-4" aria-hidden="true" /><span data-copy-label>{{ __('Copy') }}</span></button></dd>
                </div>
                <div class="rounded-xl border border-dashed border-line bg-page p-5">
                    <dt class="flex items-center gap-2 text-sm text-muted"><x-heroicon-m-key class="size-4 text-brand" aria-hidden="true" />{{ __('Code') }}</dt>
                    <dd class="mt-2 select-all font-mono text-2xl font-semibold tracking-[0.3em]">{{ $pin }}</dd>
                    <dd class="mt-3"><button type="button" class="chip min-h-9 gap-1 px-3 text-sm" data-copy="{{ $pin }}" data-copied="{{ __('Copied') }}" hidden><x-heroicon-m-clipboard class="size-4" aria-hidden="true" /><span data-copy-label>{{ __('Copy') }}</span></button></dd>
                </div>
            </dl>

            <p class="mt-5 flex items-start gap-3 rounded-lg border border-danger px-4 py-3">
                <x-heroicon-m-exclamation-triangle class="mt-0.5 size-5 shrink-0 text-danger" aria-hidden="true" />
                <span>{{ __('The code is shown this once only. It is not kept where anyone could read it, so it cannot be given again. Write both down, take a picture of this page, or print it.') }}</span>
            </p>

            <p class="mt-5 flex flex-wrap gap-3 print:hidden">
                <button type="button" class="btn btn-line" data-print hidden><x-heroicon-m-printer class="size-5" aria-hidden="true" />{{ __('Print') }}</button>
                <a href="{{ route('complaint.track') }}" class="btn btn-brand"><x-heroicon-m-magnifying-glass class="size-5" aria-hidden="true" />{{ __('Follow a matter already sent') }}</a>
            </p>
        </section>

        <section class="mt-10" aria-labelledby="next-heading">
            <h2 id="next-heading" class="text-2xl">{{ __('What happens next') }}</h2>
            <ul class="mt-4 space-y-3">
                <li class="flex items-start gap-3"><x-heroicon-m-inbox-arrow-down class="mt-1 size-5 shrink-0 text-brand" aria-hidden="true" /><span>{{ __('Staff responsible read it and check the first facts.') }}</span></li>
                <li class="flex items-start gap-3"><x-heroicon-m-arrow-path class="mt-1 size-5 shrink-0 text-brand" aria-hidden="true" /><span>{{ __('It is passed to the group of work it is about.') }}</span></li>
                <li class="flex items-start gap-3"><x-heroicon-m-clock class="mt-1 size-5 shrink-0 text-brand" aria-hidden="true" /><span>{{ trans_choice('{1} A matter is dealt with within :count day of being received.|[2,*] A matter is dealt with within :count days of being received.', $days, ['count' => $days]) }} {{ __('What was done is written on the tracking page.') }}</span></li>
            </ul>
        </section>
    </div>
</x-layouts.site>
