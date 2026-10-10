{{--
    /contact: how to reach the hospital. Everything here comes from System > Site settings; what is blank is left out.

    There is no form on this page on purpose: the site keeps nothing about its visitors. Complaints and
    suggestions go to the forms the hospital already runs, which open in a new tab.
--}}
@php
    use App\Support\SiteSettings;

    $name = SiteSettings::name();
    $address = SiteSettings::get('contact.address');
    $phone = SiteSettings::get('contact.phone');
    $emergency = SiteSettings::get('contact.emergency_phone');
    $email = SiteSettings::get('contact.email');
    $hours = SiteSettings::get('contact.hours');
    $map = SiteSettings::mapEmbedUrl();
    $forms = SiteSettings::feedbackLinks();
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
@endphp
<x-layouts.site :title="__('Contact us')" :description="__('Address, phone numbers, opening hours and a map for :name.', ['name' => $name])">
    <div class="wrap py-10 sm:py-14">
        <p class="eyebrow">{{ __('Contact us') }}</p>
        <h1 class="mt-2 text-3xl sm:text-4xl">{{ $name }}</h1>

        @if ($emergency)
            <a href="{{ $dial($emergency) }}" class="card mt-8 flex flex-wrap items-center justify-between gap-3 border-danger text-ink no-underline">
                <span>
                    <span class="block text-sm text-danger">{{ __('Emergency, 24 hours a day') }}</span>
                    <span class="block font-mono text-3xl font-semibold text-danger">{{ $emergency }}</span>
                </span>
                <span class="btn btn-danger"><x-heroicon-s-phone class="size-5" aria-hidden="true" />{{ __('Call now') }}</span>
            </a>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <dl class="card grid content-start gap-4 sm:grid-cols-[8rem_1fr]">
                @if ($address)
                    <dt class="font-mono text-sm text-syn-function">{{ __('Address') }}</dt>
                    <dd class="whitespace-pre-line">{{ $address }}</dd>
                @endif
                @if ($phone)
                    <dt class="font-mono text-sm text-syn-function">{{ __('Phone') }}</dt>
                    <dd><a href="{{ $dial($phone) }}" class="font-semibold text-brand underline">{{ $phone }}</a></dd>
                @endif
                @if ($email)
                    <dt class="font-mono text-sm text-syn-function">{{ __('Email') }}</dt>
                    <dd><a href="mailto:{{ $email }}" class="break-all font-semibold text-brand underline">{{ $email }}</a></dd>
                @endif
                @if ($hours)
                    <dt class="font-mono text-sm text-syn-function">{{ __('Opening hours') }}</dt>
                    <dd class="whitespace-pre-line">{{ $hours }}</dd>
                @endif
            </dl>

            @if ($map)
                {{--
                    The map comes from Google, which learns the visitor's address when it loads. So it loads
                    only when the visitor asks for it; until then nothing is fetched from anywhere but this site.
                --}}
                <div class="card flex min-h-72 flex-col items-center justify-center gap-4 overflow-hidden p-0 text-center" data-map="{{ $map }}">
                    <x-heroicon-o-map-pin class="size-10 text-brand" aria-hidden="true" />
                    <button type="button" class="btn btn-line" data-map-show>{{ __('Show the map') }}</button>
                    <p class="max-w-xs px-4 text-sm text-muted">{{ __('The map is loaded from Google Maps when you press the button.') }}</p>
                </div>
                <script>
                    document.querySelector('[data-map-show]').addEventListener('click', function () {
                        var box = this.closest('[data-map]');
                        var frame = document.createElement('iframe');
                        frame.src = box.dataset.map;
                        frame.title = @json(__('Map'));
                        frame.loading = 'lazy';
                        frame.referrerPolicy = 'no-referrer';
                        frame.className = 'h-96 w-full border-0';
                        box.replaceChildren(frame);
                    });
                </script>
            @endif
        </div>

        @if ($forms)
            <section class="mt-12">
                <h2 class="eyebrow">{{ __('Complaints and suggestions') }}</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    @foreach ($forms as $label => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="card flex items-center justify-between gap-3 text-ink no-underline">
                            <span>{{ __($label) }}</span>
                            <x-heroicon-m-arrow-top-right-on-square class="size-5 shrink-0 text-brand" aria-hidden="true" />
                            <span class="sr-only">{{ __('(opens in a new tab)') }}</span>
                        </a>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-muted">{{ __('These forms are run on Google Forms and open in a new tab. This website itself does not collect or keep personal data about its visitors.') }}</p>
            </section>
        @endif
    </div>
</x-layouts.site>
