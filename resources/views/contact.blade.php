{{--
    /contact: how to reach the hospital. Everything here comes from System > Site settings; what is blank is left out.
    From the top: the numbers to call, large; then where it is, when it is open and how to write, beside the map;
    then the forms for complaints and suggestions; then the other ways in.

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
    $social = SiteSettings::socialLinks();
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
    $facts = $address || $email || $hours;
@endphp
<x-layouts.site :title="__('Contact us')" :description="__('Address, phone numbers, opening hours and a map for :name.', ['name' => $name])">
    <x-site.page-header :eyebrow="__('Contact us')" :title="$name" icon="phone" :lead="__('Call, write or come in: here is how.')" />

    <div class="wrap py-10 sm:py-14">
        {{-- The numbers first: most people who open this page want to call. --}}
        @if ($emergency || $phone)
            <div @class(['grid gap-4', 'sm:grid-cols-2' => $emergency && $phone])>
                @if ($emergency)
                    <a href="{{ $dial($emergency) }}" class="card flex flex-wrap items-center justify-between gap-4 border-danger text-ink no-underline">
                        <span>
                            <span class="block text-sm text-danger">{{ __('Emergency, 24 hours a day') }}</span>
                            <span class="block font-mono text-4xl font-semibold text-danger">{{ $emergency }}</span>
                        </span>
                        <span class="btn btn-danger"><x-heroicon-s-phone class="size-5" aria-hidden="true" />{{ __('Call now') }}</span>
                    </a>
                @endif
                @if ($phone)
                    <a href="{{ $dial($phone) }}" class="card flex flex-wrap items-center justify-between gap-4 text-ink no-underline">
                        <span>
                            <span class="block text-sm text-muted">{{ __('The hospital') }}</span>
                            <span class="block font-mono text-4xl font-semibold text-brand">{{ $phone }}</span>
                        </span>
                        <span class="btn btn-brand"><x-heroicon-s-phone class="size-5" aria-hidden="true" />{{ __('Call now') }}</span>
                    </a>
                @endif
            </div>
        @endif

        @if ($facts || $map)
            <div @class(['grid items-start gap-6', 'mt-6' => $emergency || $phone, 'lg:grid-cols-2' => $facts && $map])>
                @if ($facts)
                    <dl class="space-y-4">
                        @if ($address)
                            <div class="card flex gap-4">
                                <span class="door-mark" aria-hidden="true"><x-heroicon-o-map-pin class="size-5" /></span>
                                <div>
                                    <dt class="font-mono text-sm text-syn-function">{{ __('Address') }}</dt>
                                    <dd class="mt-1 whitespace-pre-line">{{ $address }}</dd>
                                </div>
                            </div>
                        @endif
                        @if ($hours)
                            <div class="card flex gap-4">
                                <span class="door-mark" aria-hidden="true"><x-heroicon-o-clock class="size-5" /></span>
                                <div>
                                    <dt class="font-mono text-sm text-syn-function">{{ __('Opening hours') }}</dt>
                                    <dd class="mt-1 whitespace-pre-line">{{ $hours }}</dd>
                                    <dd class="mt-2 text-sm"><a href="{{ route('schedule') }}" class="font-semibold text-brand underline">{{ __('Clinic schedule') }} &rarr;</a></dd>
                                </div>
                            </div>
                        @endif
                        @if ($email)
                            <div class="card flex gap-4">
                                <span class="door-mark" aria-hidden="true"><x-heroicon-o-envelope class="size-5" /></span>
                                <div class="min-w-0">
                                    <dt class="font-mono text-sm text-syn-function">{{ __('Email') }}</dt>
                                    <dd class="mt-1"><a href="mailto:{{ $email }}" class="break-all font-semibold text-brand underline">{{ $email }}</a></dd>
                                </div>
                            </div>
                        @endif
                    </dl>
                @endif

                @if ($map)
                    {{--
                        The map comes from Google, which learns the visitor's address when it loads. So it loads
                        only when the visitor asks for it; until then nothing is fetched from anywhere but this site.
                    --}}
                    <div class="card flex min-h-80 flex-col items-center justify-center gap-4 overflow-hidden p-0 text-center" data-map="{{ $map }}">
                        <span class="flex size-16 items-center justify-center rounded-full bg-raised text-brand" aria-hidden="true"><x-heroicon-o-map class="size-8" /></span>
                        <button type="button" class="btn btn-brand" data-map-show>{{ __('Show the map') }}</button>
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
        @endif

        {{-- The way to complain, suggest or praise, and to follow what was sent: a page of its own (/complaint). --}}
        <section class="mt-12">
            <a href="{{ route('complaint') }}" class="card flex flex-wrap items-center gap-4 border-brand text-ink no-underline">
                <span class="door-mark" aria-hidden="true"><x-heroicon-o-chat-bubble-left-right class="size-5" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block text-lg font-semibold">{{ __('Complaints and suggestions') }}</span>
                    <span class="block text-muted">{{ __('How to send a matter, what is taken, and how to follow what is done about it.') }}</span>
                </span>
                <x-heroicon-m-arrow-right class="size-5 shrink-0 text-brand" aria-hidden="true" />
            </a>
        </section>

        @if ($forms)
            <section class="mt-8">
                <h2 class="text-2xl">{{ __('Forms') }}</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    @foreach ($forms as $label => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="card flex items-center gap-4 text-ink no-underline">
                            <span class="door-mark" aria-hidden="true"><x-heroicon-o-chat-bubble-left-right class="size-5" /></span>
                            <span class="min-w-0 flex-1 font-medium">{{ __($label) }}</span>
                            <x-heroicon-m-arrow-top-right-on-square class="size-5 shrink-0 text-brand" aria-hidden="true" />
                            <span class="sr-only">{{ __('(opens in a new tab)') }}</span>
                        </a>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-muted">{{ __('These forms are run on Google Forms and open in a new tab. This website itself does not collect or keep personal data about its visitors.') }}</p>
            </section>
        @endif

        {{-- The other ways in: every department's own number, the departments themselves, and where else the hospital is found. --}}
        <section class="mt-12">
            <h2 class="text-2xl">{{ __('More ways to reach us') }}</h2>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (array_merge([
                    [route('phones'), 'Phone numbers of every department', 'phone', false],
                    [route('departments.index'), 'Departments', 'building-office-2', false],
                    [route('about'), 'About us', 'information-circle', false],
                ], array_map(fn (string $network, string $url) => [$url, $network, 'globe-alt', true], array_keys($social), $social)) as [$address, $label, $mark, $outside])
                    <li>
                        <a href="{{ $address }}" class="card flex h-full items-center gap-4 text-ink no-underline" @if ($outside) target="_blank" rel="noopener noreferrer" @endif>
                            <span class="door-mark" aria-hidden="true"><x-dynamic-component :component="'heroicon-o-'.$mark" class="size-5" /></span>
                            <span class="min-w-0 flex-1 font-medium">{{ $outside ? $label : __($label) }}</span>
                            <x-dynamic-component :component="$outside ? 'heroicon-m-arrow-top-right-on-square' : 'heroicon-m-chevron-right'" class="size-5 shrink-0 text-brand" aria-hidden="true" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</x-layouts.site>
