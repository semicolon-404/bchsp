{{-- The home page. Its sections (announcements, articles, departments...) are added as each is built. --}}
@php
    use App\Support\SiteSettings;

    $phone = SiteSettings::get('contact.phone');
    $emergency = SiteSettings::get('contact.emergency_phone');
    $dial = fn (string $number) => 'tel:'.preg_replace('/[^0-9+]/', '', $number);
@endphp
<x-layouts.site :description="__('News, announcements and services of :name.', ['name' => config('app.name')])">
    <section class="border-b border-line bg-surface">
        <div class="wrap py-14 text-center sm:py-20">
            <p class="mx-auto inline-flex items-center gap-2 rounded-full border border-line bg-page px-4 py-1 text-sm font-semibold text-brand">
                <x-heroicon-s-heart class="size-4" aria-hidden="true" />
                {{ __('Caring for the community') }}
            </p>
            <h1 class="mx-auto mt-5 max-w-3xl text-3xl font-bold sm:text-5xl">{{ config('app.name') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-muted">{{ __('News, announcements and services of the hospital, in one place.') }}</p>

            @if ($phone || $emergency)
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    @if ($phone)
                        <a href="{{ $dial($phone) }}" class="btn btn-brand">
                            <x-heroicon-s-phone class="size-5" aria-hidden="true" />
                            {{ __('Call :number', ['number' => $phone]) }}
                        </a>
                    @endif
                    @if ($emergency)
                        <a href="{{ $dial($emergency) }}" class="btn btn-line">
                            {{ __('Emergency :number', ['number' => $emergency]) }}
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </section>
</x-layouts.site>
